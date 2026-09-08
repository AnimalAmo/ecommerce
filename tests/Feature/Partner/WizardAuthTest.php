<?php

namespace Tests\Feature\Partner;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Gli step dei tre wizard di onboarding (struttura, attività, smartbox) toccano
 * tutti la bozza via InteractsWithStructureDraft, che scrive `user_id` =>
 * Auth::id(): da ospite quel campo è null, quindi un anonimo creerebbe bozze
 * orfane e caricherebbe foto sul disco pubblico. Devono stare dietro al login.
 *
 * Le rotte NON sono elencate a mano: si leggono dal router per prefisso, così
 * uno step aggiunto domani fuori dal gruppo autenticato fa fallire il test
 * invece di passare inosservato. È questo il vero scopo del file.
 */
class WizardAuthTest extends TestCase
{
    use RefreshDatabase;

    /** Prefissi dei tre wizard: ogni rotta sotto questi nomi scrive sulla bozza. */
    private const WIZARD_PREFIXES = [
        'partner.structure.',
        'partner.activity.',
        'partner.smartbox.',
    ];

    /**
     * Il funnel pubblico: qui arriva chi partner non è ancora. Sono le sole
     * rotte `partner.*`/candidatura che devono restare aperte agli ospiti.
     */
    private const PUBLIC_FUNNEL = [
        'work-with-us',
        'work-with-us.thanks',
        'partner.register',
        'partner.register.step2',
    ];

    /** Primo step dei tre wizard: l'unico raggiungibile senza una bozza avviata. */
    private const WIZARD_ENTRY_POINTS = [
        'partner.structure.type',
        'partner.activity.type',
        'partner.smartbox.type',
    ];

    /**
     * Nomi delle rotte wizard letti dal router.
     *
     * @return list<string>
     */
    private function wizardRouteNames(): array
    {
        return collect(Route::getRoutes()->getRoutesByName())
            ->keys()
            ->filter(fn (string $name): bool => Str::startsWith($name, self::WIZARD_PREFIXES))
            ->values()
            ->all();
    }

    /**
     * Se l'enumerazione tornasse vuota (rinomina dei prefissi, rotte spostate)
     * il ciclo qui sotto passerebbe a vuoto: questa è la sua rete di sicurezza.
     */
    public function test_the_wizard_routes_are_read_from_the_router(): void
    {
        $names = $this->wizardRouteNames();

        $this->assertGreaterThanOrEqual(30, count($names), 'I tre wizard hanno decine di step: enumerazione sospetta.');

        foreach (self::WIZARD_ENTRY_POINTS as $entry) {
            $this->assertContains($entry, $names);
        }
    }

    public function test_a_guest_cannot_open_any_wizard_step(): void
    {
        // Niente pagina /login in questo progetto: `redirectGuestsTo` in
        // bootstrap/app.php manda gli ospiti alla home (cfr. PartnerAccessControlTest).
        $home = route('home');

        $leaking = [];

        foreach ($this->wizardRouteNames() as $name) {
            if (! $this->get(route($name))->isRedirect($home)) {
                $leaking[] = $name;
            }
        }

        $this->assertSame([], $leaking, 'Step del wizard raggiungibili da ospite: '.implode(', ', $leaking));
    }

    /** Il danno concreto del wizard aperto: bozze orfane create da anonimi. */
    public function test_a_guest_visit_creates_no_draft(): void
    {
        foreach (self::WIZARD_ENTRY_POINTS as $entry) {
            $this->get(route($entry));
        }

        $this->assertDatabaseCount('structure_drafts', 0);
    }

    public function test_an_active_partner_can_open_the_first_step_of_each_wizard(): void
    {
        $this->actingAsActivePartner();

        foreach (self::WIZARD_ENTRY_POINTS as $entry) {
            $this->get(route($entry))->assertOk();
        }
    }

    /** Chiudere il wizard non deve chiudere l'iscrizione: qui arrivano gli ospiti. */
    public function test_the_public_signup_funnel_stays_open_to_guests(): void
    {
        foreach (self::PUBLIC_FUNNEL as $name) {
            $this->get(route($name))->assertOk();
        }
    }
}
