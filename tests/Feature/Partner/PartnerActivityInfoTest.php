<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityInfo;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Step 5 "Informazioni generali". Dal 27/09/2026 (risposte della cliente) la
 * data è FACOLTATIVA per un'attività e obbligatoria per un evento, e qui stanno
 * anche prenotazione (entrambi i rami), ricorrenza e posti disponibili (solo
 * eventi).
 */
class PartnerActivityInfoTest extends TestCase
{
    use RefreshDatabase;

    /** Bozza del ramo richiesto, messa in sessione (ospite: `user_id` nullo). */
    private function draftInSession(string $type): StructureDraft
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 5, 'type' => $type]);
        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    public function test_page_renders_the_date_fields(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.activity.info'))
            ->assertOk()
            ->assertSee(__('partner.activity_info.heading'))
            ->assertSee(__('partner.activity_info.step'))
            ->assertSee(__('partner.activity_info.date_start'))
            ->assertSee(__('partner.activity_info.date_end'))
            ->assertSee(__('partner.activity_info.next'));
    }

    /**
     * L'input date nativo mostrava il calendario del browser, diverso su ogni
     * sistema e fuori dalla grafica: al suo posto il date-picker di Flux, che è
     * un bottone — per questo le misure dell'XD vanno su [&_button].
     */
    public function test_the_dates_use_the_flux_date_picker(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.activity.info'))
            ->assertOk()
            ->assertSee('data-flux-date-picker', false)
            ->assertDontSee('type="date"', false);
    }

    /**
     * Era `test_activity_needs_only_the_dates`, e il nome è diventato falso il
     * 27/09/2026: un'attività non ha bisogno NEMMENO delle date. Quel che il
     * test prova davvero — che non le si chiedano gli orari, e che una coppia di
     * date valide passi — resta, sotto un nome che non promette più
     * un'obbligatorietà che non c'è.
     */
    public function test_an_activity_is_not_asked_for_the_times(): void
    {
        $this->draftInSession('attivita');

        Livewire::test(ActivityInfo::class)
            ->assertSet('form.isEvent', false)
            ->assertDontSee(__('partner.activity_info.time_start'))
            ->set('form.dateStart', '2026-08-01')
            ->set('form.dateEnd', '2026-08-03')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.included'));

        $this->assertDatabaseHas('structure_drafts', ['current_step' => 5]);
    }

    /**
     * Il caso vero della richiesta: un toelettatore non ha una data. Passa lo
     * step, e le colonne restano NULL — non la data di oggi, che
     * `Carbon::parse('')` produrrebbe e che il gate di pubblicazione leggerebbe
     * come una data esistente.
     */
    public function test_an_activity_saves_without_any_date(): void
    {
        $draft = $this->draftInSession('attivita');

        Livewire::test(ActivityInfo::class)
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.included'));

        $draft->refresh();
        $this->assertNull($draft->date_start);
        $this->assertNull($draft->date_end);
    }

    /**
     * Una data di fine da sola non è un dato, è una riga rotta: il publisher ne
     * farebbe un `ends_at` senza `starts_at`. È `required_with:dateEnd`, e il
     * caso è nato con la data facoltativa.
     */
    public function test_an_end_date_without_a_start_is_refused_on_an_activity(): void
    {
        $this->draftInSession('attivita');

        Livewire::test(ActivityInfo::class)
            ->set('form.dateEnd', '2026-08-03')
            ->call('next')
            ->assertHasErrors(['form.dateStart' => 'required_with']);

        $this->assertNull(StructureDraft::first()->date_end);
    }

    /** Un evento invece le vuole tutte due: qui la data resta obbligatoria. */
    public function test_an_event_still_requires_the_start_date(): void
    {
        $this->draftInSession('eventi');

        Livewire::test(ActivityInfo::class)
            ->set('form.timeStart', '10:00')
            ->set('form.timeEnd', '18:00')
            ->call('next')
            ->assertHasErrors(['form.dateStart' => 'required', 'form.dateEnd' => 'required']);
    }

    public function test_event_also_requires_the_times(): void
    {
        $this->draftInSession('eventi');

        Livewire::test(ActivityInfo::class)
            ->assertSet('form.isEvent', true)
            ->assertSee(__('partner.activity_info.time_start'))
            ->set('form.dateStart', '2026-08-01')
            ->set('form.dateEnd', '2026-08-01')
            ->call('next')
            ->assertHasErrors('form.timeStart')
            ->set('form.timeStart', '10:00')
            ->set('form.timeEnd', '18:00')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('structure_drafts', ['time_start' => '10:00', 'time_end' => '18:00']);
    }

    public function test_end_date_must_not_precede_start_date(): void
    {
        Livewire::test(ActivityInfo::class)
            ->set('form.dateStart', '2026-08-05')
            ->set('form.dateEnd', '2026-08-01')
            ->call('next')
            ->assertHasErrors('form.dateEnd');
    }

    /**
     * La prenotazione la cliente la chiede a entrambi i rami («possibilità di
     * prenotazione» ai professionisti, «obbligatoria o facoltativa» agli
     * eventi): un gruppo solo, un campo solo, fuori dal ramo.
     */
    public function test_the_booking_requirement_is_saved_on_an_activity(): void
    {
        $draft = $this->draftInSession('attivita');

        Livewire::test(ActivityInfo::class)
            ->assertSee(__('partner.activity_info.booking_requirement'))
            ->set('form.bookingRequirement', 'facoltativa')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame('facoltativa', $draft->refresh()->booking_requirement);
    }

    public function test_the_booking_requirement_is_saved_on_an_event(): void
    {
        $draft = $this->draftInSession('eventi');

        Livewire::test(ActivityInfo::class)
            ->set('form.dateStart', '2026-08-01')
            ->set('form.dateEnd', '2026-08-01')
            ->set('form.timeStart', '10:00')
            ->set('form.timeEnd', '18:00')
            ->set('form.bookingRequirement', 'obbligatoria')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame('obbligatoria', $draft->refresh()->booking_requirement);
    }

    /** Vuoto è una risposta valida: la colonna torna NULL, non stringa vuota. */
    public function test_an_empty_booking_requirement_stays_null(): void
    {
        $draft = $this->draftInSession('attivita');

        Livewire::test(ActivityInfo::class)
            ->call('next')
            ->assertHasNoErrors();

        $this->assertNull($draft->refresh()->booking_requirement);
    }

    /** La whitelist è `ServiceOptionLabels::slugs('booking_requirement')`: la property è client-settable. */
    public function test_an_unknown_booking_requirement_is_refused(): void
    {
        $this->draftInSession('attivita');

        Livewire::test(ActivityInfo::class)
            ->set('form.bookingRequirement', 'forse')
            ->call('next')
            ->assertHasErrors(['form.bookingRequirement' => 'in']);
    }

    /**
     * Ricorrenza e posti sono degli eventi: sul ramo professionale non si
     * disegnano e non si scrivono. `toDraft()` non ne scrive nemmeno la chiave,
     * quindi un valore rimasto in memoria non arriva alla bozza.
     */
    public function test_recurrence_and_seats_belong_to_events_only(): void
    {
        $draft = $this->draftInSession('attivita');

        Livewire::test(ActivityInfo::class)
            ->assertDontSee(__('partner.activity_info.recurrence'))
            ->assertDontSee(__('partner.activity_info.max_participants'))
            ->set('form.recurrence', 'ricorrente')
            ->set('form.maxParticipants', '30')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();
        $this->assertNull($draft->recurrence);
        $this->assertNull($draft->max_participants);
    }

    public function test_an_event_saves_the_recurrence_and_the_seats(): void
    {
        $draft = $this->draftInSession('eventi');

        Livewire::test(ActivityInfo::class)
            ->assertSee(__('partner.activity_info.recurrence'))
            ->assertSee(__('partner.activity_info.max_participants'))
            ->set('form.dateStart', '2026-08-01')
            ->set('form.dateEnd', '2026-08-01')
            ->set('form.timeStart', '10:00')
            ->set('form.timeEnd', '18:00')
            ->set('form.recurrence', 'ricorrente')
            ->set('form.maxParticipants', '30')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();
        $this->assertSame('ricorrente', $draft->recurrence);
        $this->assertSame(30, $draft->max_participants);
    }

    /**
     * Posti vuoti = nessun limite. La colonna è castata `integer`, quindi una
     * stringa vuota diventerebbe 0: un evento esaurito prima di aprire.
     */
    public function test_empty_seats_stay_null_and_do_not_become_zero(): void
    {
        $draft = $this->draftInSession('eventi');

        Livewire::test(ActivityInfo::class)
            ->set('form.dateStart', '2026-08-01')
            ->set('form.dateEnd', '2026-08-01')
            ->set('form.timeStart', '10:00')
            ->set('form.timeEnd', '18:00')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertNull($draft->refresh()->max_participants);
    }

    /**
     * Il tetto è quello della colonna (`unsignedSmallInteger` su bozza ed
     * evento): in MySQL strict un valore più grande è un errore SQL, non una
     * validazione. La suite gira su SQLite, dove passerebbe in silenzio.
     */
    public function test_seats_above_the_column_ceiling_are_refused(): void
    {
        $this->draftInSession('eventi');

        Livewire::test(ActivityInfo::class)
            ->set('form.dateStart', '2026-08-01')
            ->set('form.dateEnd', '2026-08-01')
            ->set('form.timeStart', '10:00')
            ->set('form.timeEnd', '18:00')
            ->set('form.maxParticipants', '65536')
            ->call('next')
            ->assertHasErrors(['form.maxParticipants' => 'max']);

        $this->assertNull(StructureDraft::first()->max_participants);
    }

    /** Zero posti non è un limite, è una scheda invendibile: `min:1`. */
    public function test_zero_seats_are_refused(): void
    {
        $this->draftInSession('eventi');

        Livewire::test(ActivityInfo::class)
            ->set('form.dateStart', '2026-08-01')
            ->set('form.dateEnd', '2026-08-01')
            ->set('form.timeStart', '10:00')
            ->set('form.timeEnd', '18:00')
            ->set('form.maxParticipants', '0')
            ->call('next')
            ->assertHasErrors(['form.maxParticipants' => 'min']);
    }

    public function test_an_unknown_recurrence_is_refused(): void
    {
        $this->draftInSession('eventi');

        Livewire::test(ActivityInfo::class)
            ->set('form.dateStart', '2026-08-01')
            ->set('form.dateEnd', '2026-08-01')
            ->set('form.timeStart', '10:00')
            ->set('form.timeEnd', '18:00')
            ->set('form.recurrence', 'ogni_luna_piena')
            ->call('next')
            ->assertHasErrors(['form.recurrence' => 'in']);
    }

    public function test_it_rehydrates_the_saved_booking_recurrence_and_seats(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 5,
            'type' => 'eventi',
            'booking_requirement' => 'non_prevista',
            'recurrence' => 'singolo',
            'max_participants' => 12,
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityInfo::class)
            ->assertSet('form.bookingRequirement', 'non_prevista')
            ->assertSet('form.recurrence', 'singolo')
            // Stringa e non int: la property è `string`, e un input svuotato
            // manda '' — che un int trasformerebbe in 0.
            ->assertSet('form.maxParticipants', '12');
    }
}
