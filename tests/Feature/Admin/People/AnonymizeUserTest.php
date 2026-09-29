<?php

namespace Tests\Feature\Admin\People;

use App\Livewire\Admin\People\UserIndex;
use App\Models\Cart\Cart;
use App\Models\Community\CommunityPost;
use App\Models\Favorite\Favorite;
use App\Models\Partner\PartnerProfile;
use App\Models\Review\Review;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\People\AnonymizeUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnonymizeUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_wipes_personal_data_and_keeps_public_content_without_the_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Marta',
            'last_name' => 'Belloni',
            'address' => 'Via Roma 1',
            'city' => 'Brescia',
            'postal_code' => '25100',
            'birth_date' => '1990-01-01',
            'newsletter' => true,
        ]);
        $user->forceFill(['stripe_payment_method_id' => 'pm_1', 'card_last4' => '4242', 'card_brand' => 'visa'])->save();

        Favorite::factory()->for($user)->create();
        $cart = Cart::factory()->for($user)->create();
        $post = CommunityPost::factory()->create(['user_id' => $user->id, 'author_name' => 'Marta']);
        $review = Review::factory()->create(['author_name' => 'Marta Belloni']);
        $review->forceFill(['user_id' => $user->id])->save();
        $originalPassword = $user->password;

        app(AnonymizeUser::class)->handle($user);

        $user->refresh();
        $this->assertSame('Utente anonimizzato', $user->first_name);
        $this->assertNull($user->address);
        $this->assertNull($user->city);
        $this->assertNull($user->postal_code);
        $this->assertNull($user->birth_date);
        $this->assertFalse($user->newsletter);
        $this->assertFalse($user->hasSavedCard());
        $this->assertNotSame($originalPassword, $user->password);
        $this->assertSame(0, $user->favorites()->count());
        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
        $this->assertDatabaseHas('community_posts', ['id' => $post->id, 'author_name' => 'Utente anonimizzato']);
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'author_name' => 'Utente anonimizzato']);
    }

    /**
     * Trovato dal tester il 28/09/2026 (WP3b); il builder della lane profilo
     * lo aveva segnalato fuori dal suo perimetro. I recapiti pubblici del
     * partner (risposta della cliente, 26/09/2026, punto 6) sono contatti che
     * la persona ha scelto di pubblicare, spesso il cellulare e l'email suoi,
     * non documenti fiscali: la conservazione che tiene gli ordini e i dati
     * fiscali del profilo non li copre. AnonymizeUser non toccava
     * partner_profiles, quindi dopo «Cancella su richiesta» telefono, WhatsApp
     * ed email restavano a database, e con loro il consenso a pubblicarli.
     *
     * Se ne vanno anche sito e indirizzo pubblico: esistono solo per essere
     * pubblicati, e l'indirizzo di un professionista che lavora da casa è il
     * suo indirizzo personale (scelta del main thread, 28/09/2026).
     */
    public function test_anonymising_a_partner_removes_the_personal_public_contacts_and_the_consent(): void
    {
        Role::findOrCreate('partner', 'web');
        $partner = User::factory()->create(['first_name' => 'Marta']);
        $partner->assignRole('partner');
        $profile = PartnerProfile::factory()->withPublicContacts()->for($partner)->create(['vat' => '12345678901']);

        app(AnonymizeUser::class)->handle($partner);

        $profile->refresh();
        $this->assertNull($profile->public_phone, 'Il telefono pubblico resta dopo l\'anonimizzazione.');
        $this->assertNull($profile->public_whatsapp, 'Il WhatsApp pubblico resta dopo l\'anonimizzazione.');
        $this->assertNull($profile->public_email, 'L\'email pubblica resta dopo l\'anonimizzazione.');
        $this->assertNull($profile->public_website, 'Il sito pubblico resta dopo l\'anonimizzazione.');
        $this->assertNull($profile->public_address, 'L\'indirizzo pubblico resta dopo l\'anonimizzazione.');
        $this->assertFalse($profile->publishesContacts(), 'Il consenso a pubblicare resta dopo l\'anonimizzazione.');
        // I dati fiscali restano: la conservazione dei documenti prevale (docblock di AnonymizeUser).
        $this->assertSame('12345678901', $profile->vat);
    }

    public function test_a_superadmin_cannot_be_anonymised(): void
    {
        $admin = $this->actingAsSuperadmin();

        $this->assertNotNull(app(AnonymizeUser::class)->blockReason($admin));

        $this->expectException(RuntimeException::class);
        app(AnonymizeUser::class)->handle($admin);
    }

    public function test_an_active_partner_with_live_catalog_items_is_refused_until_suspended(): void
    {
        Role::findOrCreate('partner', 'web');
        $partner = User::factory()->create();
        $partner->assignRole('partner');
        $structure = Structure::factory()->create(['user_id' => $partner->id]);

        $service = app(AnonymizeUser::class);
        $this->assertStringContainsString('partner attivo', (string) $service->blockReason($partner));

        $structure->forceFill(['suspended_at' => now()])->save();

        $this->assertNull($service->blockReason($partner));
    }

    public function test_the_panel_modal_explains_a_refusal_and_changes_nothing(): void
    {
        $this->actingAsSuperadmin();
        Role::findOrCreate('partner', 'web');
        $partner = User::factory()->create(['first_name' => 'Pia']);
        $partner->assignRole('partner');
        Structure::factory()->create(['user_id' => $partner->id]);

        Livewire::test(UserIndex::class)
            ->call('askAnonymize', $partner->id)
            ->assertSee('sospendi prima le sue schede')
            ->assertDontSee('Cancella i dati')
            ->call('anonymize');

        $this->assertNull($partner->fresh()->anonymized_at);
    }
}
