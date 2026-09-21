<?php

namespace Tests\Feature\Mail;

use App\Mail\PartnerInvitationMail;
use App\Models\MailDelivery;
use App\Models\Partner\PartnerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Il webhook Mailgun è l'unico posto in cui l'esito reale di una mail entra
 * nel progetto: i log Mailgun scadono in pochi giorni sul piano in uso, e
 * senza questa tabella "non è arrivata a X" resta una parola della cliente
 * contro un'altra. Vedi docs/mailgun-setup.md §9.
 */
class MailgunWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'signing-key-di-test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mailgun.webhook_signing_key' => self::KEY]);
    }

    /** @param  array<string, mixed>  $eventData */
    private function postEvent(array $eventData, ?string $signature = null, ?string $timestamp = null): TestResponse
    {
        $timestamp ??= (string) time();
        $token = 'token-'.md5((string) mt_rand());

        return $this->postJson(route('webhooks.mailgun'), [
            'signature' => [
                'timestamp' => $timestamp,
                'token' => $token,
                'signature' => $signature ?? hash_hmac('sha256', $timestamp.$token, self::KEY),
            ],
            'event-data' => $eventData,
        ]);
    }

    /** @param  array<string, mixed>  $overrides */
    private function failureEvent(string $messageId, array $overrides = []): array
    {
        return array_merge([
            'event' => 'failed',
            'severity' => 'permanent',
            'recipient' => 'info@babaresidences.comm',
            'reason' => 'generic',
            'delivery-status' => [
                'code' => 550,
                'message' => 'No MX for babaresidences.comm',
            ],
            'message' => ['headers' => ['message-id' => $messageId]],
        ], $overrides);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $this->postEvent($this->failureEvent('abc@mg.animalamo.it'), signature: 'firma-inventata')
            ->assertForbidden();

        $this->assertSame(0, MailDelivery::count());
    }

    /**
     * Firma valida ma vecchia: è il replay di un payload catturato. Mailgun
     * consiglia una finestra sui minuti, non solo il confronto HMAC.
     */
    public function test_an_expired_timestamp_is_rejected(): void
    {
        $old = (string) (time() - 3600);
        $token = 'token-vecchio';

        $this->postJson(route('webhooks.mailgun'), [
            'signature' => [
                'timestamp' => $old,
                'token' => $token,
                'signature' => hash_hmac('sha256', $old.$token, self::KEY),
            ],
            'event-data' => $this->failureEvent('abc@mg.animalamo.it'),
        ])->assertForbidden();
    }

    /**
     * La firma non copre l'evento: un token già visto si rifiuta, o una firma
     * catturata potrebbe accompagnare un evento qualsiasi.
     */
    public function test_a_replayed_token_has_no_effect(): void
    {
        $delivery = MailDelivery::create([
            'message_id' => 'abc@mg.animalamo.it',
            'recipient' => 'info@babaresidences.comm',
            'status' => MailDelivery::STATUS_SENT,
        ]);
        $timestamp = (string) time();
        $signature = ['timestamp' => $timestamp, 'token' => 'token-unico', 'signature' => hash_hmac('sha256', $timestamp.'token-unico', self::KEY)];

        $this->postJson(route('webhooks.mailgun'), [
            'signature' => $signature,
            'event-data' => ['event' => 'delivered', 'recipient' => 'info@babaresidences.comm', 'message' => ['headers' => ['message-id' => 'abc@mg.animalamo.it']]],
        ])->assertOk()->assertJson(['status' => 'ok']);

        $this->postJson(route('webhooks.mailgun'), [
            'signature' => $signature,
            'event-data' => $this->failureEvent('abc@mg.animalamo.it'),
        ])->assertOk()->assertJson(['status' => 'duplicate']);

        $this->assertSame(MailDelivery::STATUS_DELIVERED, $delivery->fresh()->status);
    }

    public function test_a_permanent_failure_is_recorded_on_the_sent_message(): void
    {
        $delivery = MailDelivery::create([
            'message_id' => 'abc@mg.animalamo.it',
            'recipient' => 'info@babaresidences.comm',
            'mailable' => PartnerInvitationMail::class,
            'status' => MailDelivery::STATUS_SENT,
        ]);

        $this->postEvent($this->failureEvent('abc@mg.animalamo.it'))->assertOk();

        $delivery->refresh();

        $this->assertSame(MailDelivery::STATUS_FAILED, $delivery->status);
        $this->assertSame('permanent', $delivery->severity);
        $this->assertStringContainsString('No MX', (string) $delivery->reason);
        $this->assertSame('550', (string) $delivery->smtp_code);
        $this->assertNotNull($delivery->last_event_at);
    }

    /** Il message-id di Mailgun arriva senza parentesi angolari: quello di Symfony le ha. */
    public function test_the_message_id_is_matched_without_angle_brackets(): void
    {
        $delivery = MailDelivery::create([
            'message_id' => 'abc@mg.animalamo.it',
            'recipient' => 'info@hotel.it',
            'status' => MailDelivery::STATUS_SENT,
        ]);

        $this->postEvent([
            'event' => 'delivered',
            'recipient' => 'info@hotel.it',
            'message' => ['headers' => ['message-id' => '<abc@mg.animalamo.it>']],
        ])->assertOk();

        $this->assertSame(MailDelivery::STATUS_DELIVERED, $delivery->refresh()->status);
    }

    /**
     * Un evento su un messaggio che non abbiamo registrato (mail partita da un
     * altro ambiente sullo stesso dominio) non va perso: la riga si crea.
     */
    public function test_an_unknown_message_still_gets_a_row(): void
    {
        $this->postEvent($this->failureEvent('sconosciuto@mg.animalamo.it'))->assertOk();

        $this->assertSame(MailDelivery::STATUS_FAILED, MailDelivery::firstOrFail()->status);
    }

    /**
     * Casella piena: Mailgun manda `failed`/`temporary`, ritenta, e poi consegna.
     * Se il rinvio temporaneo pesasse come uno definitivo, la riga resterebbe
     * fallita e risponderemmo alla cliente che la mail non è arrivata.
     */
    public function test_a_temporary_failure_does_not_block_the_later_delivery(): void
    {
        $this->postEvent($this->failureEvent('abc@mg.animalamo.it', [
            'severity' => 'temporary',
            'recipient' => 'info@hotel.it',
            'delivery-status' => ['code' => 451, 'message' => 'Mailbox busy'],
        ]))->assertOk();

        $this->assertSame(MailDelivery::STATUS_DEFERRED, MailDelivery::firstOrFail()->status);

        $this->postEvent([
            'event' => 'delivered',
            'recipient' => 'info@hotel.it',
            'message' => ['headers' => ['message-id' => 'abc@mg.animalamo.it']],
        ])->assertOk();

        $this->assertSame(MailDelivery::STATUS_DELIVERED, MailDelivery::firstOrFail()->status);
    }

    /** Un `accepted` che arriva dopo il `delivered` non deve riportare indietro lo stato. */
    public function test_a_late_event_does_not_downgrade_the_status(): void
    {
        MailDelivery::create([
            'message_id' => 'abc@mg.animalamo.it',
            'recipient' => 'info@hotel.it',
            'status' => MailDelivery::STATUS_DELIVERED,
        ]);

        $this->postEvent([
            'event' => 'accepted',
            'recipient' => 'info@hotel.it',
            'message' => ['headers' => ['message-id' => 'abc@mg.animalamo.it']],
        ])->assertOk();

        $this->assertSame(MailDelivery::STATUS_DELIVERED, MailDelivery::firstOrFail()->status);
    }

    /** Ogni mail che parte lascia una riga, altrimenti il webhook non ha a cosa agganciarsi. */
    public function test_every_sent_message_is_recorded(): void
    {
        $application = PartnerApplication::create([
            'first_name' => 'Susanna',
            'last_name' => 'Rossi',
            'email' => 'info@hotelrosovino.it',
            'phone' => '3498798828',
            'city' => 'Milano',
            'business_name' => 'Hotel Rosovino',
            'role' => 'Titolare',
            'offer_type' => 'Struttura ricettiva',
            'description' => 'Hotel pet friendly.',
        ]);

        Mail::to($application->email)->send(new PartnerInvitationMail($application, 'https://animalamo.it/x'));

        $delivery = MailDelivery::firstOrFail();

        $this->assertSame('info@hotelrosovino.it', $delivery->recipient);
        $this->assertSame(PartnerInvitationMail::class, $delivery->mailable);
        $this->assertSame(MailDelivery::STATUS_SENT, $delivery->status);
        $this->assertNotSame('', $delivery->message_id);
    }
}
