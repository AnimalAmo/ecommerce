<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityAnimalServices;
use App\Livewire\Partner\Activity\ActivityCancellation;
use App\Livewire\Partner\Activity\ActivityIncluded;
use App\Livewire\Partner\Smartbox\SmartboxIncluded;
use App\Livewire\Partner\Smartbox\SmartboxIncludedAnimals;
use App\Livewire\Partner\Smartbox\SmartboxOffers;
use App\Livewire\Partner\Smartbox\SmartboxPrice;
use App\Livewire\Partner\Structure\HotelAnimalServices;
use App\Livewire\Partner\Structure\HotelPayment;
use App\Livewire\Partner\Structure\HotelServices;
use App\Models\Amenity\Amenity;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\FamilyPublisher;
use App\Services\Partner\ServiceOptionLabels;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * WP8 (piano del 27/09/2026), dalla spunta del partner alla scheda del cliente.
 *
 * La cliente, 26-27/09/2026: «Servizi attualmente non selezionabili. Non li
 * toglierei dal catalogo. Vorrei invece renderli selezionabili dove
 * pertinenti». Sono sette voci del catalogo amenity che nessuno slug del
 * wizard raggiungeva. Il buco aveva anche l'altro verso: cinque slug che il
 * partner spuntava e che non avevano una riga a catalogo, quindi la scheda non
 * li mostrava mai (più un sesto, il campo da tennis della smartbox, trovato
 * dal tester e coperto a parte in fondo al file).
 *
 * Ogni voce si prova per intero, una volta per famiglia: la casella c'è nello
 * step giusto, la spunta arriva alla bozza, l'ultimo step pubblica, il pivot
 * la porta con included=true e la pagina pubblica la stampa. Con una sola
 * spunta la scheda deve mostrare quella voce e nessun'altra: è lo stesso
 * controllo che dice «una voce non spuntata non compare».
 */
class SelectableAmenitiesFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');

        $this->seed([RegionSeeder::class, ProvinceSeeder::class, AmenitySeeder::class]);
    }

    /**
     * Le dodici voci: slug del wizard, gruppo di ServiceOptionLabels che lo
     * disegna, nome della voce a catalogo (quello che legge il cliente) e
     * gruppo della voce.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function twelveItems(): array
    {
        return [
            // Le sette della cliente (26-27/09/2026).
            'lavanderia' => ['lavanderia', 'services', 'Lavanderia', Amenity::GROUP_HOTEL],
            'ascensore' => ['ascensore', 'services', 'Ascensore', Amenity::GROUP_HOTEL],
            'noleggio bici' => ['noleggio_bici', 'services', 'Noleggio bici', Amenity::GROUP_HOTEL],
            'dog sitter' => ['dog_sitter', 'animal_services', 'Dog sitter', Amenity::GROUP_ANIMAL],
            'dog beach' => ['dog_beach', 'animal_services', 'Dog Beach nelle vicinanze', Amenity::GROUP_ANIMAL],
            'supplemento animali' => ['supplemento_animali', 'animal_services', 'Supplemento animali', Amenity::GROUP_ANIMAL],
            'piscina per cani' => ['piscina_cani', 'animal_services', 'Piscina per cani', Amenity::GROUP_ANIMAL],
            // Le cinque orfane: spuntabili, senza riga a catalogo fino al 28/09/2026.
            'tv' => ['tv', 'services', 'TV', Amenity::GROUP_HOTEL],
            'riscaldamento' => ['riscaldamento', 'services', 'Riscaldamento', Amenity::GROUP_HOTEL],
            'ricarica elettrica' => ['ricarica_elettrica', 'services', 'Ricarica auto elettriche', Amenity::GROUP_HOTEL],
            'piscina' => ['piscina', 'services', 'Piscina', Amenity::GROUP_HOTEL],
            'area animali' => ['area_animali', 'animal_services', 'Area dedicata agli animali', Amenity::GROUP_ANIMAL],
        ];
    }

    // ---------------------------------------------------------------------
    // La mappa, nei due versi
    // ---------------------------------------------------------------------

    /**
     * Ogni slug dei due gruppi che diventano righe della scheda ha la sua
     * voce, nel gruppo giusto; ogni voce del catalogo ha almeno uno slug che
     * la raggiunge. È il difetto di WP8 nei due versi: se un giorno si
     * aggiunge uno slug senza la riga (o una riga senza lo slug) esce qui, e
     * non da una segnalazione della cliente.
     */
    public function test_the_map_is_complete_in_both_directions(): void
    {
        $catalog = [];

        foreach (AmenitySeeder::AMENITIES as $group => $names) {
            foreach ($names as $name) {
                $catalog[$name] = $group;
            }
        }

        foreach (['services' => Amenity::GROUP_HOTEL, 'animal_services' => Amenity::GROUP_ANIMAL] as $optionGroup => $amenityGroup) {
            foreach (array_diff(ServiceOptionLabels::slugs($optionGroup), ['nessuno', 'altro']) as $slug) {
                $this->assertArrayHasKey($slug, FamilyPublisher::AMENITY_MAP, "lo slug {$optionGroup}.{$slug} si spunta e non arriva a nessuna voce");
                $this->assertSame($amenityGroup, $catalog[FamilyPublisher::AMENITY_MAP[$slug]] ?? null, "{$slug} va su una voce che il catalogo non ha, o in un altro gruppo");
            }
        }

        // Nessuna voce del catalogo resta irraggiungibile, e la mappa non
        // punta a nomi che il catalogo non ha (un refuso sarebbe una spunta
        // persa in silenzio).
        $this->assertEqualsCanonicalizing(array_keys($catalog), array_values(array_unique(FamilyPublisher::AMENITY_MAP)));

        // E il database seminato è quel catalogo, con i gruppi giusti.
        $this->assertEqualsCanonicalizing($catalog, Amenity::query()->pluck('group', 'name')->all());
    }

    /**
     * Le sette della cliente: il partner spunta le stesse parole che il
     * cliente poi legge sulla scheda. Una casella «Dog beach» che diventa
     * «Dog Beach nelle vicinanze» andrebbe bene lo stesso, ma una che diventa
     * un'altra cosa no, e qui si vede subito.
     */
    public function test_the_partner_ticks_the_same_words_the_customer_reads(): void
    {
        foreach (array_slice(self::twelveItems(), 0, 7) as [$slug, $optionGroup, $name]) {
            $this->assertSame($name, ServiceOptionLabels::options($optionGroup)[$slug] ?? null, "etichetta di {$slug}");
        }
    }

    /** La richiesta chiedeva di non cambiarla: 'sauna' resta la voce «Spa». */
    public function test_sauna_is_still_shown_as_spa(): void
    {
        $this->assertSame(['Spa'], $this->includedNames(FamilyPublisher::amenityPivot(['sauna'])));
    }

    // ---------------------------------------------------------------------
    // Dalla spunta alla scheda, una famiglia per test
    // ---------------------------------------------------------------------

    #[DataProvider('twelveItems')]
    public function test_a_hotel_shows_the_item_it_ticked_and_nothing_else(string $slug, string $optionGroup, string $name, string $amenityGroup): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = $this->draftInSession($partner, [
            'current_step' => 10,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Bau Resort'],
            'description' => ['it' => 'Hotel pet friendly sul lago.'],
            'address' => 'Via Roma 1',
            'city' => 'Brescia',
            'province' => 'BS',
            'zip' => '25100',
            'rooms' => [['type' => 'doppia', 'count' => 3, 'price' => '80']],
            'cancellation_when' => '7',
            'photos' => ['structure-photos/cover.jpg'],
        ]);

        $optionGroup === 'services'
            ? $this->tick(HotelServices::class, 'form.services', $draft, 'services', $slug)
            : $this->tick(HotelAnimalServices::class, 'services', $draft, 'animal_services', $slug);

        Livewire::test(HotelPayment::class)->call('skip')->assertRedirect(route('partner.dashboard'));

        $structure = Structure::withHidden()->where('structure_draft_id', $draft->id)->sole();
        $this->assertOnlyIncluded($structure, $name);

        $html = $this->publicPage(route('holiday.structure', [
            'region' => Region::query()->findOrFail($structure->region_id)->slug,
            'structure' => $structure->slug,
        ]));

        $this->assertSame([['srv-'.$amenityGroup, $name]], $this->shownRows($html));
    }

    #[DataProvider('twelveItems')]
    public function test_an_activity_shows_the_item_it_ticked_and_nothing_else(string $slug, string $optionGroup, string $name, string $amenityGroup): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = $this->draftInSession($partner, $this->activityAttributes('attivita'));

        $optionGroup === 'services'
            ? $this->tick(ActivityIncluded::class, 'form.services', $draft, 'services', $slug)
            : $this->tick(ActivityAnimalServices::class, 'services', $draft, 'animal_services', $slug);

        Livewire::test(ActivityCancellation::class)->set('when', '7')->call('next')->assertRedirect(route('partner.dashboard'));

        $activity = Event::withHidden()->where('structure_draft_id', $draft->id)->sole();
        $this->assertOnlyIncluded($activity, $name);
        $this->assertSame([$name], array_column($activity->amenityRows($amenityGroup), 'label'));

        $html = $this->publicPage(route('eventi.activity', ['activity' => $activity->slug]));

        $this->assertSame([$name], array_column($this->shownRows($html), 1));
    }

    #[DataProvider('twelveItems')]
    public function test_a_smartbox_shows_the_item_it_ticked_and_nothing_else(string $slug, string $optionGroup, string $name, string $amenityGroup): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = $this->draftInSession($partner, [
            'current_step' => 11,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Weekend Zen col tuo cane'],
            'description' => ['it' => 'Relax e coccole per entrambi.'],
            'duration_days' => 2,
            'cancellation_when' => '15',
            'photos' => ['smartbox-photos/zen.jpg'],
        ]);

        // Nella smartbox i servizi della struttura stanno in `included_services`
        // (step «Cosa è incluso»), non in `services`.
        $optionGroup === 'services'
            ? $this->tick(SmartboxIncluded::class, 'included', $draft, 'included_services', $slug)
            : $this->tick(SmartboxIncludedAnimals::class, 'services', $draft, 'animal_services', $slug);

        Livewire::test(SmartboxPrice::class)->set('price', '215')->call('save')->assertRedirect(route('partner.dashboard'));

        $package = SmartboxPackage::withHidden()->where('structure_draft_id', $draft->id)->sole();
        $this->assertOnlyIncluded($package, $name);

        $html = $this->publicPage(route('smartbox.detail', ['box' => $package->slug]));

        $this->assertSame([['srv-'.$amenityGroup, $name]], $this->shownRows($html));
    }

    /**
     * Il ramo «Eventi» del wizard attività usa gli stessi step e un'altra
     * pagina pubblica (EventDetail): una prova sola, con una voce per gruppo.
     */
    public function test_an_event_shows_the_items_it_ticked(): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = $this->draftInSession($partner, $this->activityAttributes('eventi'));

        $this->tick(ActivityIncluded::class, 'form.services', $draft, 'services', 'ascensore');
        $this->tick(ActivityAnimalServices::class, 'services', $draft, 'animal_services', 'supplemento_animali');

        Livewire::test(ActivityCancellation::class)->set('when', '7')->call('next')->assertRedirect(route('partner.dashboard'));

        $event = Event::withHidden()->where('structure_draft_id', $draft->id)->sole();
        $html = $this->publicPage(route('eventi.detail', ['event' => $event->slug]));

        $this->assertSame(['Ascensore', 'Supplemento animali'], array_column($this->shownRows($html), 1));
    }

    /**
     * Le tre viste dei servizi animali tengono ognuna la sua mappa delle
     * descrizioni (ServiceOptionLabels non le conosce): le quattro voci nuove
     * devono avere il sottotitolo in tutte e tre, in italiano e in inglese.
     */
    public function test_every_animal_step_describes_the_four_new_items(): void
    {
        $partner = $this->actingAsActivePartner();
        $keys = ['opt_dogsitter_desc', 'opt_dog_beach_desc', 'opt_surcharge_desc', 'opt_dog_pool_desc'];

        foreach (['it', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach ([
                'struttura' => HotelAnimalServices::class,
                'attivita' => ActivityAnimalServices::class,
                'smartbox' => SmartboxIncludedAnimals::class,
            ] as $family => $component) {
                $this->draftInSession($partner, ['service_category' => $family, 'current_step' => 4]);
                $step = Livewire::test($component);

                foreach ($keys as $key) {
                    $text = __('partner.hotel_animal_services.'.$key);
                    $this->assertStringNotContainsString('hotel_animal_services', $text, "{$locale}: chiave {$key} mancante");
                    $step->assertSee($text);
                }
            }
        }
    }

    // ---------------------------------------------------------------------
    // «Piscina» e «Piscina per cani»
    // ---------------------------------------------------------------------

    /**
     * Due voci di due gruppi, non una: la piscina delle persone sta fra i
     * servizi della struttura, quella dei cani fra i servizi per gli animali.
     * Spuntarne una non deve accendere l'altra, in nessuno dei due versi.
     */
    public function test_piscina_and_piscina_per_cani_are_two_items(): void
    {
        $pool = Amenity::query()->where('name', 'Piscina')->sole();
        $dogPool = Amenity::query()->where('name', 'Piscina per cani')->sole();

        $this->assertNotSame($pool->id, $dogPool->id);
        $this->assertSame(Amenity::GROUP_HOTEL, $pool->group);
        $this->assertSame(Amenity::GROUP_ANIMAL, $dogPool->group);

        $this->assertSame(['Piscina'], $this->includedNames(FamilyPublisher::amenityPivot(['piscina'])));
        $this->assertSame(['Piscina per cani'], $this->includedNames(FamilyPublisher::amenityPivot(['piscina_cani'])));

        // 'piscina' degli aggiuntivi smartbox è la stessa voce delle persone,
        // non quella dei cani.
        $this->assertSame(['Piscina', 'Spa'], $this->includedNames(FamilyPublisher::amenityPivot(['piscina', 'spa'])));
    }

    /** Entrambe spuntate: due righe, ognuna nel suo riquadro della scheda. */
    public function test_a_hotel_with_both_pools_shows_both_in_their_own_box(): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = $this->draftInSession($partner, [
            'current_step' => 10,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Due Piscine'],
            'province' => 'BS',
            'rooms' => [['type' => 'doppia', 'count' => 1, 'price' => '90']],
            'photos' => ['structure-photos/cover.jpg'],
        ]);

        $this->tick(HotelServices::class, 'form.services', $draft, 'services', 'piscina');
        $this->tick(HotelAnimalServices::class, 'services', $draft, 'animal_services', 'piscina_cani');

        Livewire::test(HotelPayment::class)->call('skip')->assertRedirect(route('partner.dashboard'));

        $structure = Structure::withHidden()->where('structure_draft_id', $draft->id)->sole();
        $html = $this->publicPage(route('holiday.structure', ['region' => 'lombardia', 'structure' => $structure->slug]));

        $this->assertSame([['srv-hotel', 'Piscina'], ['srv-animal', 'Piscina per cani']], $this->shownRows($html));
    }

    // ---------------------------------------------------------------------
    // Validazione degli step
    // ---------------------------------------------------------------------

    /**
     * Gli step che scrivono in bozza uno slug che il publisher legge: famiglia,
     * componente, proprietà, colonna della bozza, uno slug valido del gruppo e
     * uno slug che il publisher conosce ma che appartiene a un ALTRO gruppo.
     *
     * @return array<string, array{0: string, 1: class-string, 2: string, 3: string, 4: string, 5: string}>
     */
    public static function stepsThatSaveServiceSlugs(): array
    {
        return [
            'hotel, servizi' => ['struttura', HotelServices::class, 'form.services', 'services', 'lavanderia', 'piscina_cani'],
            'hotel, aggiuntivi' => ['struttura', HotelServices::class, 'form.additional', 'additional_services', 'colazione', 'spa'],
            'hotel, regole' => ['struttura', HotelServices::class, 'form.structureRules', 'rules', 'vietato_fumare', 'dog_beach'],
            'hotel, animali' => ['struttura', HotelAnimalServices::class, 'services', 'animal_services', 'dog_beach', 'piscina'],
            'attività, servizi' => ['attivita', ActivityIncluded::class, 'form.services', 'services', 'tv', 'area_animali'],
            'attività, aggiuntivi' => ['attivita', ActivityIncluded::class, 'form.additional', 'additional_services', 'pranzo', 'lavanderia'],
            'attività, regole' => ['attivita', ActivityIncluded::class, 'form.structureRules', 'rules', 'vietato_feste', 'ascensore'],
            'attività, animali' => ['attivita', ActivityAnimalServices::class, 'services', 'animal_services', 'piscina_cani', 'noleggio_bici'],
            'smartbox, inclusi' => ['smartbox', SmartboxIncluded::class, 'included', 'included_services', 'noleggio_bici', 'supplemento_animali'],
            'smartbox, animali' => ['smartbox', SmartboxIncludedAnimals::class, 'services', 'animal_services', 'supplemento_animali', 'riscaldamento'],
            // Review del 28/09/2026: lo step «Cosa troverai» mancava, e la sua
            // whitelist non la provava nessuno.
            'smartbox, cosa troverai' => ['smartbox', SmartboxOffers::class, 'amenities', 'services', 'bagno', 'wifi'],
            'smartbox, aggiuntivi' => ['smartbox', SmartboxOffers::class, 'additional', 'additional_services', 'campo_da_tennis', 'piscina_cani'],
        ];
    }

    /**
     * Uno slug inventato dal payload, o uno vero ma di un altro gruppo, non
     * arriva alla bozza: lo step si ferma sull'`in:` e la bozza resta com'era.
     * Il secondo caso è quello che conta dal 28/09/2026: AMENITY_MAP ora
     * conosce 'piscina_cani', e senza l'`in:` una «Piscina per cani» forzata
     * nei servizi della struttura sarebbe arrivata fino alla scheda.
     */
    #[DataProvider('stepsThatSaveServiceSlugs')]
    public function test_a_slug_the_step_does_not_draw_does_not_reach_the_draft(string $family, string $component, string $property, string $column, string $known, string $foreign): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->draftInSession($partner, ['service_category' => $family, 'current_step' => 4, $column => [$known]]);

        Livewire::test($component)
            ->set($property, [$known, 'jacuzzi', $foreign])
            ->call('next')
            ->assertHasErrors([$property.'.1' => 'in', $property.'.2' => 'in'])
            ->assertNoRedirect();

        $fresh = $draft->fresh();
        $this->assertSame([$known], $fresh->{$column});
        $this->assertSame(4, $fresh->current_step);
    }

    /**
     * La rilettura della bozza tiene solo gli slug che lo step disegna
     * (aggiunta della lane viste): una bozza con uno slug vecchio
     * (DemoUserSeeder semina 'servizio_veterinario' e 'parcheggio'), o di un
     * altro gruppo, non deve bloccare il partner su un errore che non vede e
     * che non ha una casella da togliere.
     */
    #[DataProvider('stepsThatSaveServiceSlugs')]
    public function test_a_slug_the_step_does_not_draw_is_dropped_when_the_step_reopens(string $family, string $component, string $property, string $column, string $known, string $foreign): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->draftInSession($partner, ['service_category' => $family, 'current_step' => 4, $column => ['servizio_veterinario', $known, 'parcheggio', $foreign]]);

        Livewire::test($component)
            ->assertSet($property, [$known])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame([$known], $draft->fresh()->{$column});
    }

    // ---------------------------------------------------------------------
    // Pannello admin
    // ---------------------------------------------------------------------

    /**
     * Il pannello crea le schede dalle stesse mappe del wizard: le dodici voci
     * devono esserci nelle tre famiglie, o la cliente non potrebbe spuntarle
     * quando crea un servizio per conto di un partner.
     */
    public function test_the_admin_panel_offers_the_twelve_items_in_every_family(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        // Il riquadro giusto, per wire:key (review del 28/09/2026): cercare
        // value="dog_sitter" in tutta la pagina lo trovava anche fra le
        // categorie professionali, e «Piscina» dentro «Piscina per cani».
        $boxes = [
            'structure' => ['services' => 'svc-', 'animal_services' => 'animal-'],
            'activity' => ['services' => 'act-svc-', 'animal_services' => 'act-animal-'],
            'smartbox' => ['services' => 'sb-inc-', 'animal_services' => 'sb-animal-'],
        ];

        foreach (['structure', 'activity', 'smartbox'] as $family) {
            $html = $this->get(route('admin.catalog.create', ['family' => $family, 'partner' => $partner->id]))
                ->assertOk()
                ->getContent();

            foreach (self::twelveItems() as [$slug, $optionGroup]) {
                $this->assertStringContainsString('wire:key="'.$boxes[$family][$optionGroup].$slug.'"', $html, "{$family}: nel riquadro {$optionGroup} del pannello manca {$slug}");
                $this->assertStringContainsString(e(ServiceOptionLabels::options($optionGroup)[$slug]), $html, "{$family}: nel pannello manca l'etichetta di {$slug}");
            }
        }
    }

    // ---------------------------------------------------------------------
    // Buco residuo dello stesso tipo
    // ---------------------------------------------------------------------

    /**
     * Trovato dal tester di WP8 il 28/09/2026 e chiuso lo stesso giorno con la
     * voce «Campo da tennis» nel gruppo hotel. Gli aggiuntivi della smartbox (step «Cosa
     * troverai», gruppo `smartbox_additional`) finiscono in
     * `additional_services`, che SmartboxPublisher passa a syncAmenities().
     * 'piscina' e 'spa' hanno la loro voce; 'campo_da_tennis' no: il partner
     * lo spunta e la scheda non lo mostra mai. È la sesta orfana, lo stesso
     * difetto delle cinque chiuse da WP8, che l'audit non aveva contato.
     */
    public function test_every_smartbox_extra_the_partner_can_tick_reaches_a_catalog_row(): void
    {
        foreach (ServiceOptionLabels::slugs('smartbox_additional') as $slug) {
            $this->assertArrayHasKey($slug, FamilyPublisher::AMENITY_MAP, "l'aggiuntivo smartbox '{$slug}' si spunta e non arriva a nessuna voce della scheda");
        }
    }

    // ---------------------------------------------------------------------
    // Aiuti
    // ---------------------------------------------------------------------

    private function draftInSession(User $partner, array $attributes): StructureDraft
    {
        $draft = StructureDraft::create(array_merge([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
        ], $attributes));
        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    /** @return array<string, mixed> bozza attività (o evento) pubblicabile, ferma prima della cancellazione */
    private function activityAttributes(string $type): array
    {
        return [
            'current_step' => 9,
            'service_category' => 'attivita',
            'type' => $type,
            'name' => ['it' => $type === 'eventi' ? 'Aperitivo a 6 zampe' : 'Passeggiata a sei zampe'],
            'description' => ['it' => 'Un pomeriggio con i vostri amici pelosi.'],
            'detailed_description' => ['it' => 'Tre ore nel parco, acqua e ciotole per tutti.'],
            'meeting_point' => ['it' => 'Piazza Duomo'],
            'address' => 'Piazza Duomo 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'date_start' => now()->addMonth()->toDateString(),
            'date_end' => now()->addMonth()->addDays(2)->toDateString(),
            'time_start' => '10:00',
            'time_end' => '18:00',
            'price_type' => 'pagamento',
            'price_per_person' => '25',
            'photos' => ['structure-photos/attivita.jpg'],
        ];
    }

    /**
     * Spunta lo slug nello step che lo disegna. Prima guarda che la casella ci
     * sia: senza, il set() di sotto sarebbe un payload che nessun partner può
     * mandare, e il test passerebbe su una voce invisibile nel wizard.
     */
    private function tick(string $component, string $property, StructureDraft $draft, string $column, string $slug): void
    {
        Livewire::test($component)
            ->assertSeeHtml('value="'.$slug.'"')
            ->set($property, [$slug])
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame([$slug], $draft->fresh()->{$column}, "la spunta di {$slug} non è arrivata a {$column}");
    }

    /** Il pivot ha una riga per ogni voce, e solo quella spuntata è inclusa. */
    private function assertOnlyIncluded(Model $row, string $name): void
    {
        $this->assertSame(Amenity::count(), $row->amenities()->count(), 'una riga di pivot per ogni voce del catalogo');
        $this->assertSame([$name], $row->amenities()->wherePivot('included', true)->pluck('name')->all());
    }

    /** La pagina come la vede un visitatore qualunque. */
    private function publicPage(string $url): string
    {
        auth()->logout();

        return $this->get($url)->assertOk()->getContent();
    }

    /**
     * Le righe «servizi» della scheda, con la chiave del riquadro: srv-hotel e
     * srv-animal su strutture e smartbox, included-<colonna> su attività ed
     * eventi. Si leggono i <li> e non la pagina intera: «Piscina» è dentro
     * «Piscina per cani», e «TV» o «Spa» possono comparire altrove.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function shownRows(string $html): array
    {
        preg_match_all('/<li\b[^>]*\bwire:key="(srv-hotel|srv-animal|included-\d+)-\d+"[^>]*>(.*?)<\/li>/s', $html, $matches, PREG_SET_ORDER);

        return array_map(
            fn (array $match): array => [$match[1], trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($match[2]), ENT_QUOTES)))],
            $matches,
        );
    }

    /**
     * @param  array<int, array{included: bool, position: int}>  $pivot
     * @return list<string>
     */
    private function includedNames(array $pivot): array
    {
        $ids = array_keys(array_filter($pivot, fn (array $row): bool => $row['included']));

        return Amenity::query()->whereIn('id', $ids)->orderBy('name')->pluck('name')->all();
    }
}
