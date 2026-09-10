<?php

namespace Tests\Feature\Partner\Publishing;

use App\Exceptions\PartnerNotPayableException;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Decisione della cliente: niente pubblicazione senza verifiche bancarie
 * complete. Con i direct charges non è una regola amministrativa ma una
 * condizione tecnica — senza account connesso il checkout non degrada a
 * commissione zero, esplode.
 */
class PublishRequiresStripeTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_partner_senza_stripe_non_pubblica(): void
    {
        $draft = $this->smartboxDraftFor(PartnerProfile::factory());

        try {
            app(DraftPublisher::class)->publish($draft);
            $this->fail('Attesa PartnerNotPayableException per partner senza onboarding Stripe.');
        } catch (PartnerNotPayableException $exception) {
            $this->assertSame(__('partner.errors.stripe_onboarding_required'), $exception->getMessage());
        }

        $this->assertSame(0, SmartboxPackage::where('structure_draft_id', $draft->id)->count());
    }

    public function test_un_partner_con_onboarding_a_meta_non_pubblica(): void
    {
        // charges_enabled ma non payouts_enabled: incasserebbe senza poter
        // essere bonificato, cioè soldi fermi sul suo saldo Stripe.
        $draft = $this->smartboxDraftFor(PartnerProfile::factory()->connected()->state([
            'stripe_payouts_enabled' => false,
        ]));

        $this->expectException(PartnerNotPayableException::class);

        app(DraftPublisher::class)->publish($draft);
    }

    public function test_un_partner_collegato_pubblica(): void
    {
        $draft = $this->smartboxDraftFor(PartnerProfile::factory()->connected());

        $published = app(DraftPublisher::class)->publish($draft);

        $this->assertInstanceOf(SmartboxPackage::class, $published);
    }

    /** Bozza smartbox completa, intestata al partner del profilo dato. */
    private function smartboxDraftFor(mixed $profileFactory): StructureDraft
    {
        $partner = User::factory()->create();
        $profileFactory->for($partner)->create();

        return StructureDraft::create([
            'user_id' => $partner->id,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto di prova', 'en' => 'Test box'],
            'price' => '120',
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
        ]);
    }
}
