<?php

namespace Tests;

use App\Models\Partner\PartnerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crea (e autentica) un utente partner ATTIVO per le rotte dell'area
     * riservata. Passa ['is_active' => false] per simulare un partner disattivato.
     */
    protected function actingAsActivePartner(array $attributes = []): User
    {
        Role::findOrCreate('partner', 'web');

        $user = User::factory()->create(array_merge(['is_active' => true], $attributes));
        $user->assignRole('partner');

        $this->actingAs($user);

        return $user;
    }

    /**
     * Partner che può davvero mandare un servizio a catalogo: con i direct
     * charges serve l'account Stripe collegato, altrimenti DraftPublisher
     * rifiuta la pubblicazione (PartnerNotPayableException).
     */
    protected function actingAsPayablePartner(array $attributes = []): User
    {
        $partner = $this->actingAsActivePartner($attributes);

        PartnerProfile::factory()->connected()->for($partner)->create();

        return $partner;
    }
}
