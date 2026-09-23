<?php

namespace App\Services\Partner;

use App\Enums\OrderPaymentMode;
use App\Exceptions\PaymentModeException;
use App\Jobs\PublishAwaitingDrafts;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\StructureDraft;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Modalità di pagamento del partner (richiesta della cliente, 22/09/2026):
 * online su AnimalAmo, oppure direttamente al partner, in struttura o sul suo
 * sito. Tiene in un posto solo la regola del cambio e la lettura. Schede,
 * carrello e checkout la chiedono più volte nella stessa richiesta, per
 * questo il service è registrato `scoped` e si ricorda i profili già letti.
 */
class PartnerPaymentModeService
{
    /** Regole del "sito dove pagare o prenotare", condivise coi form che lo scrivono. */
    public const PAYMENT_URL_RULES = ['nullable', 'string', 'max:255', 'url:http,https'];

    /** @var array<int, PartnerProfile|null> profili letti per user id, anche quelli assenti */
    private array $profiles = [];

    /**
     * Si rifiuta solo il passaggio da offline a online di chi non è pagabile.
     * Chi è già online senza Stripe (appena iscritto, o creato dall'admin) può
     * salvare il link senza che gli si chieda altro. Passare a offline è sempre
     * permesso: gli ordini online passati continuano a essere bonificati,
     * perché canBePaid() non cambia.
     *
     * Il link finisce come href nelle pagine B2C e nelle mail: lo si verifica
     * qui, per ogni chiamante, anche se il form lo ha già validato.
     *
     * @throws PaymentModeException
     * @throws ValidationException link non http/https o troppo lungo, sotto la chiave `paymentUrl`
     */
    public function set(PartnerProfile $profile, bool $online, ?string $paymentUrl): PartnerProfile
    {
        if ($online && ! $profile->canSwitchToOnline()) {
            throw PaymentModeException::stripeRequired();
        }

        $url = trim((string) $paymentUrl);
        $url = $url === '' ? null : $url;

        Validator::make(['paymentUrl' => $url], ['paymentUrl' => self::PAYMENT_URL_RULES])->validate();

        $profile->fill([
            'online_payment' => $online,
            'payment_url' => $url,
        ])->save();

        $this->profiles[(int) $profile->user_id] = $profile;

        $this->publishAwaitingDrafts($profile);

        return $profile;
    }

    /** Senza profilo (o senza titolare) vale il comportamento di prima: online. */
    public function forOwner(?int $userId): OrderPaymentMode
    {
        return $this->profileFor($userId)?->paymentMode() ?? OrderPaymentMode::Online;
    }

    /** Structure, Event o SmartboxPackage: decide il titolare della scheda. */
    public function forPurchasable(?Model $purchasable): OrderPaymentMode
    {
        $owner = $purchasable?->getAttribute('user_id');

        return $this->forOwner($owner === null ? null : (int) $owner);
    }

    /** Profilo del venditore, per ragione sociale e link nel checkout e nelle viste. */
    public function profileFor(?int $userId): ?PartnerProfile
    {
        if ($userId === null) {
            return null;
        }

        if (! array_key_exists($userId, $this->profiles)) {
            $this->profiles[$userId] = PartnerProfile::query()->where('user_id', $userId)->first();
        }

        return $this->profiles[$userId];
    }

    /**
     * Passare al pagamento diretto, o tornare online da pagabile, può
     * sbloccare i servizi rimasti in attesa di Stripe (P4). La dispatch parte
     * solo se ce n'è almeno uno. Stessa protezione di StripeConnectService:
     * con la coda sync il job gira qui dentro, e un suo errore non deve far
     * sembrare fallito un cambio di modalità già salvato.
     *
     * Attenzione per P2/P3: se set() viene chiamato dentro un DB::transaction
     * esterno, con afterCommit il job gira al commit, fuori da questo
     * try/catch, e un suo errore risale al chiamante.
     */
    private function publishAwaitingDrafts(PartnerProfile $profile): void
    {
        if ($profile->user_id === null || ! $profile->canPublish()) {
            return;
        }

        $awaiting = StructureDraft::query()
            ->where('user_id', $profile->user_id)
            ->awaitingPublication()
            ->exists();

        if (! $awaiting) {
            return;
        }

        try {
            PublishAwaitingDrafts::dispatch((int) $profile->user_id);
        } catch (Throwable $exception) {
            Log::warning('Pubblicazione delle bozze in attesa non avviata', [
                'partner_user_id' => $profile->user_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
