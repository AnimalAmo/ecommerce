<?php

namespace Tests\Feature;

use App\Livewire\Catalog\AnimalHoliday;
use App\Livewire\Catalog\AnimalHolidayRegion;
use App\Livewire\Catalog\Events;
use App\Livewire\Catalog\HomePage;
use App\Livewire\Catalog\Smartbox;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Venue\Venue;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    // ============ A) Filtro "Dove" ============

    public function test_events_filter_by_dove_matches_title_location_or_venue(): void
    {
        $venue = Venue::factory()->create(['name' => 'Arena di Verona']);

        Event::factory()->create(['title' => 'Brunch a Roma', 'location' => 'Roma, Italia']);
        Event::factory()->create(['title' => 'Concerto', 'location' => 'Verona, Italia', 'venue_id' => $venue->id]);
        Event::factory()->create(['title' => 'Trekking', 'location' => 'Milano, Italia']);

        // Match su location
        Livewire::test(Events::class)
            ->set('where', 'Roma')
            ->assertSee('Brunch a Roma')
            ->assertDontSee('Concerto')
            ->assertDontSee('Trekking');

        // Match sul nome della venue (whereHas)
        Livewire::test(Events::class)
            ->set('where', 'Arena')
            ->assertSee('Concerto')
            ->assertDontSee('Brunch a Roma');

        // Match sul titolo
        Livewire::test(Events::class)
            ->set('where', 'Trekking')
            ->assertSee('Trekking')
            ->assertDontSee('Brunch a Roma');
    }

    public function test_events_filter_is_case_insensitive_and_trimmed(): void
    {
        Event::factory()->create(['title' => 'Evento uno', 'location' => 'Bologna, Italia']);
        Event::factory()->create(['title' => 'Evento due', 'location' => 'Napoli, Italia']);

        Livewire::test(Events::class)
            ->set('where', '  bOLOGna ')
            ->assertSee('Evento uno')
            ->assertDontSee('Evento due');
    }

    public function test_events_search_resets_pagination_to_page_one(): void
    {
        // 20 eventi a Torino → 2 pagine (PER_PAGE = 12); il filtro deve riportare a pagina 1.
        Event::factory()->count(20)->sequence(fn ($sequence) => [
            'title' => 'Torino evento '.$sequence->index,
            'location' => 'Torino, Italia',
        ])->create();

        Livewire::test(Events::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('where', 'Torino')
            ->call('search')
            ->assertSet('paginators.page', 1);
    }

    public function test_animal_holiday_filters_regions_by_dove_query_param(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);
        Region::factory()->create(['name' => 'Lazio', 'slug' => 'lazio', 'position' => 2]);

        Livewire::withQueryParams(['dove' => 'Lombardia'])
            ->test(AnimalHoliday::class)
            ->assertSee('Lombardia')
            ->assertDontSee('Lazio');
    }

    public function test_animal_holiday_shows_empty_state_when_no_region_matches(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);

        Livewire::test(AnimalHoliday::class)
            ->set('where', 'Atlantide')
            ->assertSee('Nessuna località trovata')
            ->assertDontSee('Lombardia');
    }

    public function test_animal_holiday_shows_all_regions_without_filter(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);
        Region::factory()->create(['name' => 'Lazio', 'slug' => 'lazio', 'position' => 2]);

        Livewire::test(AnimalHoliday::class)
            ->assertSee('Lombardia')
            ->assertSee('Lazio');
    }

    public function test_region_page_keeps_all_structures_when_dove_equals_region_name(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);
        Structure::factory()->create(['name' => 'Hotel Alfa', 'location' => 'Milano, Italia', 'position' => 1]);
        Structure::factory()->create(['name' => 'Hotel Beta', 'location' => 'Bergamo, Italia', 'position' => 2]);

        // where pre-compilato col nome regione → mostra tutte le strutture mock.
        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->assertSet('where', 'Lombardia')
            ->assertSee('Hotel Alfa')
            ->assertSee('Hotel Beta');
    }

    public function test_region_page_filters_structures_when_user_changes_dove(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);
        Structure::factory()->create(['name' => 'Hotel Alfa', 'location' => 'Milano, Italia', 'position' => 1]);
        Structure::factory()->create(['name' => 'Hotel Beta', 'location' => 'Bergamo, Italia', 'position' => 2]);

        // Filtro per location
        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('where', 'Milano')
            ->call('search')
            ->assertSee('Hotel Alfa')
            ->assertDontSee('Hotel Beta');

        // Filtro per nome
        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('where', 'Beta')
            ->call('search')
            ->assertSee('Hotel Beta')
            ->assertDontSee('Hotel Alfa');
    }

    public function test_home_search_redirects_to_holiday_with_dove_query(): void
    {
        Livewire::test(HomePage::class)
            ->set('where', 'Lombardia')
            ->call('search')
            ->assertRedirect(route('holiday', ['dove' => 'Lombardia']));
    }

    public function test_home_search_carries_datepicker_dates_in_redirect(): void
    {
        $checkIn = new DateTimeImmutable('+7 days');
        $checkOut = new DateTimeImmutable('+9 days');

        Livewire::test(HomePage::class)
            ->set('where', 'Lazio')
            ->call('selectDay', $checkIn->format('Y-m-d'))
            ->call('selectDay', $checkOut->format('Y-m-d'))
            ->call('search')
            ->assertRedirect(route('holiday', [
                'dove' => 'Lazio',
                'checkin' => $checkIn->format('d/m/Y'),
                'checkout' => $checkOut->format('d/m/Y'),
            ]));
    }

    // ============ B) Datepicker "Quando" ============

    public function test_home_datepicker_selects_check_in_and_check_out_range(): void
    {
        $checkIn = new DateTimeImmutable('+3 days');
        $checkOut = new DateTimeImmutable('+6 days');

        Livewire::test(HomePage::class)
            ->call('selectDay', $checkIn->format('Y-m-d'))
            ->assertSet('editCheckIn', $checkIn->format('d/m/Y'))
            ->assertSet('editCheckOut', null)
            ->call('selectDay', $checkOut->format('Y-m-d'))
            ->assertSet('editCheckIn', $checkIn->format('d/m/Y'))
            ->assertSet('editCheckOut', $checkOut->format('d/m/Y'));
    }

    public function test_events_datepicker_selects_range_without_filtering_list(): void
    {
        Event::factory()->create(['title' => 'Evento datato', 'location' => 'Pisa, Italia']);

        $checkIn = new DateTimeImmutable('+3 days');
        $checkOut = new DateTimeImmutable('+5 days');

        // Il "Quando" raccoglie le date ma NON filtra la lista (step disponibilità).
        Livewire::test(Events::class)
            ->call('selectDay', $checkIn->format('Y-m-d'))
            ->call('selectDay', $checkOut->format('Y-m-d'))
            ->assertSet('editCheckIn', $checkIn->format('d/m/Y'))
            ->assertSet('editCheckOut', $checkOut->format('d/m/Y'))
            ->assertSee('Evento datato');
    }

    // ── Difetto W9: il titolo è JSON, e il LIKE cambia col motore ──────────────
    //
    // `whereLike('title->it', $term)` con `caseSensitive` falso — il default —
    // compila un `like` nudo. Su SQLite (la suite) il LIKE è case-insensitive
    // sull'ASCII, quindi cercare «weekend» trova «Weekend di escursioni». Su MySQL
    // (la produzione) il valore che esce da `json_unquote(json_extract(...))` porta
    // la collation BINARIA e lo stesso LIKE diventa case-sensitive: la ricerca non
    // trova nulla. Il difetto è mascherato in parte dall'OR su `location`, che è
    // una varchar con collation ci.
    //
    // Su questo host il comportamento su MySQL non è eseguibile (PHP 7.4, suite su
    // SQLite in memoria), quindi la prova è sul SQL generato: il confronto sul path
    // JSON deve piegare esplicitamente il caso (LOWER su entrambi i lati o un
    // COLLATE), così l'esito non dipende dal motore. DA VERIFICARE SU MySQL con la
    // prova comportamentale del test qui sotto.

    public function test_il_confronto_sul_titolo_json_non_dipende_dal_motore(): void
    {
        Event::factory()->create(['title' => 'Weekend di escursioni', 'location' => 'Aosta, Italia']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test(Events::class)->set('where', 'weekend');

        $jsonPredicates = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains($query, 'json_extract'));

        $this->assertNotEmpty($jsonPredicates, 'Il filtro "Dove" deve interrogare il path JSON del titolo.');

        foreach ($jsonPredicates as $query) {
            $normalized = strtolower($query);

            $this->assertTrue(
                str_contains($normalized, 'lower(') || str_contains($normalized, 'collate'),
                'Il LIKE sul path JSON del titolo deve piegare il caso esplicitamente: senza, passa in '
                ."suite su SQLite e non trova nulla su MySQL.\nSQL: {$query}",
            );
        }
    }

    /** La prova comportamentale: verde su SQLite, è su MySQL che va rieseguita. */
    public function test_il_titolo_si_cerca_in_minuscolo(): void
    {
        Event::factory()->create(['title' => 'Weekend di escursioni', 'location' => 'Aosta, Italia']);
        Event::factory()->create(['title' => 'Altro evento', 'location' => 'Bari, Italia']);

        Livewire::test(Events::class)
            ->set('where', 'weekend')
            ->assertSee('Weekend di escursioni')
            ->assertDontSee('Altro evento');
    }

    /**
     * L'altra metà di W9: `operating_area` non entra in nessun ramo del filtro,
     * quindi un professionista che dichiara «Milano e provincia» ma è registrato
     * a Sesto San Giovanni non è trovabile cercando «Milano» — che è esattamente
     * il modo in cui un cliente lo cerca.
     */
    public function test_un_professionista_e_trovabile_dalla_zona_in_cui_opera(): void
    {
        Event::factory()->activity(3)->create([
            'title' => 'Toelettatura Bau',
            'location' => 'Sesto San Giovanni, Italia',
            'operating_area' => ['it' => 'Milano e provincia'],
        ]);

        Livewire::test(Events::class)
            ->set('where', 'Milano')
            ->assertSee('Toelettatura Bau');
    }

    // ── Giro del tester, 28/09/2026: W9 su tutte le pagine che cercano ────────
    //
    // La prova sul SQL qui sopra guarda solo la pagina Eventi. La stessa
    // ricerca sul JSON tradotto vive anche nella pagina Smartbox e nei tre rami
    // della pagina regione (strutture, attività ed eventi, cofanetti): senza
    // prova, una di queste poteva restare col LIKE nudo e la suite, su SQLite,
    // non se ne sarebbe accorta.

    /**
     * Esegue la ricerca e controlla ogni query che fa un LIKE sul JSON: il
     * valore estratto non deve arrivare nudo al `like` (su MySQL ha collation
     * binaria), deve passare da lower().
     *
     * @return list<string> le query con il LIKE sul JSON
     */
    private function assertEveryJsonLikeFoldsTheCase(callable $search): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $search();

        $queries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains($query, 'json_extract') && str_contains(strtolower($query), ' like '))
            ->values()
            ->all();

        $this->assertNotEmpty($queries, 'La ricerca deve interrogare il JSON tradotto.');

        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression(
                '/json_extract\("[a-z_]+", \'\$\."[a-z]+"\'\)\s+like/i',
                $query,
                "LIKE nudo sul path JSON: passa su SQLite e non trova niente su MySQL.\nSQL: {$query}",
            );
            $this->assertStringContainsString('lower(', strtolower($query), "SQL: {$query}");
        }

        return $queries;
    }

    public function test_la_pagina_smartbox_cerca_il_titolo_senza_distinguere_il_caso(): void
    {
        SmartboxPackage::factory()->create(['title' => 'Fuga nelle Dolomiti', 'slug' => 'fuga-dolomiti', 'position' => 1]);
        SmartboxPackage::factory()->create(['title' => 'Relax al lago', 'slug' => 'relax-lago', 'position' => 2]);

        $this->assertEveryJsonLikeFoldsTheCase(function (): void {
            Livewire::test(Smartbox::class)
                ->set('where', 'dolomiti')
                ->call('search')
                ->assertSee('Fuga nelle Dolomiti')
                ->assertDontSee('Relax al lago');
        });
    }

    public function test_la_pagina_regione_piega_il_caso_in_tutti_e_tre_i_rami(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);
        Structure::factory()->create(['name' => 'Hotel Alfa', 'location' => 'Milano, Italia', 'position' => 1]);
        Event::factory()->create(['title' => 'Weekend di escursioni', 'location' => 'Aosta, Italia']);
        SmartboxPackage::factory()->create(['title' => 'Weekend nelle Langhe', 'slug' => 'weekend-langhe', 'position' => 1]);

        $queries = $this->assertEveryJsonLikeFoldsTheCase(function (): void {
            Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
                ->set('activeTypes', ['hotel', 'servizi', 'attivita', 'eventi', 'smartbox'])
                ->set('where', 'weekend')
                ->call('search')
                ->assertSee('Weekend di escursioni')
                ->assertSee('Weekend nelle Langhe')
                ->assertDontSee('Hotel Alfa');
        });

        // I tre rami: nome della struttura, titolo e zona dell'evento, titolo del cofanetto.
        $all = strtolower(implode("\n", $queries));
        $this->assertStringContainsString('"structures"', $all);
        $this->assertStringContainsString('"events"', $all);
        $this->assertStringContainsString('"smartbox_packages"', $all);
        $this->assertStringContainsString('operating_area', $all);
    }

    /** L'altra metà di W9 anche sulla pagina regione: il professionista si trova dalla zona in cui opera. */
    public function test_la_pagina_regione_trova_un_professionista_dalla_zona(): void
    {
        Region::factory()->create(['name' => 'Lombardia', 'slug' => 'lombardia', 'position' => 1]);
        Event::factory()->activity(3)->create([
            'title' => 'Toelettatura Bau',
            'location' => 'Sesto San Giovanni, Italia',
            'operating_area' => ['it' => 'Milano e provincia'],
        ]);
        Event::factory()->activity(3)->create(['title' => 'Dog sitter a Bari', 'location' => 'Bari, Italia']);

        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('activeTypes', ['attivita'])
            ->set('where', 'milano')
            ->call('search')
            ->assertSee('Toelettatura Bau')
            ->assertDontSee('Dog sitter a Bari');
    }

    /** La zona si cerca senza distinguere il caso, come il titolo. */
    public function test_la_zona_si_cerca_in_minuscolo(): void
    {
        Event::factory()->activity(3)->create([
            'title' => 'Toelettatura Bau',
            'location' => 'Sesto San Giovanni, Italia',
            'operating_area' => ['it' => 'Milano e Provincia'],
        ]);

        $this->assertEveryJsonLikeFoldsTheCase(function (): void {
            Livewire::test(Events::class)
                ->set('where', 'PROVINCIA')
                ->assertSee('Toelettatura Bau');
        });
    }

    /**
     * Su /en una scheda senza titolo inglese mostra quello italiano (ripiego di
     * spatie): cercandolo, la si deve trovare. Il ramo del ripiego è SQL solo
     * fuori dall'italiano, quindi nessuna prova lo eseguiva.
     */
    public function test_in_inglese_si_trova_una_scheda_col_solo_titolo_italiano(): void
    {
        app()->setLocale('en');
        Event::factory()->create(['title' => ['it' => 'Weekend di escursioni'], 'location' => 'Aosta, Italia']);
        Event::factory()->create(['title' => ['it' => 'Altro evento', 'en' => 'Another event'], 'location' => 'Bari, Italia']);

        $queries = $this->assertEveryJsonLikeFoldsTheCase(function (): void {
            Livewire::test(Events::class)
                ->set('where', 'weekend')
                ->assertSee('Weekend di escursioni')
                ->assertDontSee('Another event');
        });

        $this->assertStringContainsString('coalesce(', strtolower(implode("\n", $queries)));
    }
}
