<?php

namespace Tests\Feature\Partner;

use App\Livewire\Forms\HotelRoomsForm;
use App\Livewire\Partner\Structure\HotelPhotos;
use App\Livewire\Partner\Structure\HotelRooms;
use App\Models\Structure\Room;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Step 5 «Stanze»: elenco di card, ogni stanza si modifica in una modale.
 */
class PartnerHotelRoomsTest extends TestCase
{
    use RefreshDatabase;

    private function hotelDraft(array $attributes = []): StructureDraft
    {
        $draft = StructureDraft::create(array_merge(['status' => 'draft', 'current_step' => 5, 'type' => 'hotel'], $attributes));
        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    /** Apre la modale (nuova stanza o `$index`) e la compila con valori validi. */
    private function fillRoom(Testable $component, ?int $index = null, array $values = []): Testable
    {
        $component->call('openRoom', $index);

        foreach (array_merge([
            'type' => 'doppia',
            'name.it' => 'Camera Lago',
            'price' => '80',
            'max_guests' => 2,
            'max_animals' => 1,
            'units' => 3,
        ], $values) as $field => $value) {
            $component->set('roomForm.'.$field, $value);
        }

        return $component;
    }

    private function setTimes(Testable $component): Testable
    {
        return $component
            ->set('form.checkinFrom', '14:00')
            ->set('form.checkinTo', '20:00')
            ->set('form.checkoutFrom', '08:00')
            ->set('form.checkoutTo', '11:00');
    }

    /** @return array<string, mixed> */
    private function row(string $key, array $overrides = []): array
    {
        return array_merge([
            'key' => $key,
            'type' => 'doppia',
            'name' => ['it' => 'Camera '.$key, 'en' => ''],
            'description' => ['it' => '', 'en' => ''],
            'price' => '80',
            'max_guests' => 2,
            'max_animals' => 1,
            'units' => 2,
            'photos' => [],
            'amenities' => [],
        ], $overrides);
    }

    public function test_page_renders_the_room_fields(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.structure.hotel.rooms'))
            ->assertOk()
            ->assertSee(__('partner.hotel_rooms.heading'))
            ->assertSee(__('partner.hotel_rooms.step'))
            ->assertSee(__('partner.hotel_rooms.add_room'))
            ->assertSee(__('partner.hotel_rooms.checkin'))
            ->assertSee(__('partner.hotel_rooms.checkout'))
            ->assertSee(__('partner.hotel_rooms.next'));
    }

    public function test_save_room_from_modal_appends_row(): void
    {
        $draft = $this->hotelDraft();

        $component = $this->fillRoom(Livewire::test(HotelRooms::class))
            ->set('roomForm.amenities', ['wifi', 'tv'])
            ->call('saveRoom')
            ->assertHasNoErrors()
            ->assertSet('roomModal', false)
            ->assertCount('form.rooms', 1)
            ->assertSet('form.rooms.0.name.it', 'Camera Lago')
            ->assertSet('form.rooms.0.units', 3)
            ->assertSet('form.rooms.0.amenities', ['wifi', 'tv']);

        $this->assertNotEmpty($component->get('form.rooms.0.key'));

        // La stanza è già nella bozza: le foto caricate non si perdono se il partner esce.
        $this->assertSame('Camera Lago', $draft->fresh()->rooms[0]['name']['it']);

        $this->fillRoom($component, null, ['name.it' => 'Suite', 'type' => 'suite'])
            ->call('saveRoom')
            ->assertCount('form.rooms', 2)
            ->assertSet('form.rooms.1.type', 'suite');
    }

    public function test_edit_room_updates_row(): void
    {
        $this->hotelDraft(['rooms' => [$this->row('a'), $this->row('b')]]);

        Livewire::test(HotelRooms::class)
            ->call('openRoom', 1)
            ->assertSet('editing', 1)
            ->assertSet('roomForm.name.it', 'Camera b')
            ->set('roomForm.price', '99.50')
            ->call('saveRoom')
            ->assertHasNoErrors()
            ->assertCount('form.rooms', 2)
            ->assertSet('form.rooms.1.price', '99.50')
            // La chiave resta quella della riga: il publisher la usa per tenere l'id della stanza.
            ->assertSet('form.rooms.1.key', 'b');
    }

    public function test_remove_room(): void
    {
        $draft = $this->hotelDraft(['rooms' => [$this->row('a'), $this->row('b')]]);

        Livewire::test(HotelRooms::class)
            ->call('removeRoom', 0)
            ->assertCount('form.rooms', 1)
            ->assertSet('form.rooms.0.key', 'b');

        $this->assertSame(['b'], array_column($draft->fresh()->rooms, 'key'));
    }

    public function test_move_room_reorders(): void
    {
        $this->hotelDraft(['rooms' => [$this->row('a'), $this->row('b'), $this->row('c')]]);

        Livewire::test(HotelRooms::class)
            ->call('moveRoom', 2, -1)
            ->assertSet('form.rooms.1.key', 'c')
            ->assertSet('form.rooms.2.key', 'b')
            // Oltre i bordi non succede niente.
            ->call('moveRoom', 0, -1)
            ->assertSet('form.rooms.0.key', 'a');
    }

    public function test_room_validation_errors(): void
    {
        $this->hotelDraft();

        $this->fillRoom(Livewire::test(HotelRooms::class), null, ['price' => '', 'max_guests' => 0])
            ->call('saveRoom')
            ->assertHasErrors(['roomForm.price', 'roomForm.max_guests'])
            ->assertCount('form.rooms', 0)
            ->assertSet('roomModal', true);
    }

    /** Spec §2: una stanza a 0 € non esiste. */
    public function test_room_price_must_be_positive(): void
    {
        $this->hotelDraft();

        $this->fillRoom(Livewire::test(HotelRooms::class), null, ['price' => '0'])
            ->call('saveRoom')
            ->assertHasErrors(['roomForm.price' => 'gt'])
            ->assertCount('form.rooms', 0);
    }

    /**
     * Le azioni della modale partono dalle righe della bozza, non da
     * `form.rooms`: righe manomesse dal client non arrivano mai nella bozza,
     * né con un moveRoom() né con «Avanti».
     */
    public function test_tampered_rows_never_reach_the_draft(): void
    {
        $draft = $this->hotelDraft(['rooms' => [$this->row('a'), $this->row('b')]]);

        $component = Livewire::test(HotelRooms::class)
            ->set('form.rooms.1.price', '-5')
            ->set('form.rooms.1.type', 'xyz')
            ->set('form.rooms.1.max_guests', 900)
            ->set('form.rooms.1.amenities', ['elicottero'])
            ->set('form.rooms.1.key', 'a')
            ->call('moveRoom', 0, 1);

        $rooms = $draft->fresh()->rooms;
        $this->assertSame(['b', 'a'], array_column($rooms, 'key'));
        $this->assertSame(['80', '80'], array_column($rooms, 'price'));
        $this->assertSame(['doppia', 'doppia'], array_column($rooms, 'type'));
        $this->assertSame([2, 2], array_column($rooms, 'max_guests'));
        $this->assertSame([[], []], array_column($rooms, 'amenities'));

        $this->setTimes($component)
            ->set('form.rooms.0.price', '-5')
            ->set('form.rooms.0.key', 'a')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame(['b', 'a'], array_column($draft->fresh()->rooms, 'key'));
        $this->assertSame(['80', '80'], array_column($draft->fresh()->rooms, 'price'));
    }

    /** La chiave di una stanza nuova la decide il server: una copiata dal client fonderebbe due stanze. */
    public function test_new_room_key_is_generated_by_the_server(): void
    {
        $draft = $this->hotelDraft(['rooms' => [$this->row('a')]]);

        $this->fillRoom(Livewire::test(HotelRooms::class))
            ->set('roomForm.key', 'a')
            ->call('saveRoom')
            ->assertHasNoErrors();

        $keys = array_column($draft->fresh()->rooms, 'key');
        $this->assertCount(2, $keys);
        $this->assertSame('a', $keys[0]);
        $this->assertNotSame('a', $keys[1]);
    }

    public function test_a_room_saves_animal_services_too(): void
    {
        $draft = $this->hotelDraft();

        $this->fillRoom(Livewire::test(HotelRooms::class))
            ->assertSee(__('partner.hotel_rooms.animal_amenities'))
            ->set('roomForm.amenities', ['wifi', 'pet_sitting'])
            ->call('saveRoom')
            ->assertHasNoErrors();

        $this->assertSame(['wifi', 'pet_sitting'], $draft->fresh()->rooms[0]['amenities']);
    }

    public function test_none_is_not_a_room_animal_service(): void
    {
        $this->hotelDraft();

        $this->fillRoom(Livewire::test(HotelRooms::class))
            ->set('roomForm.amenities', ['nessuno'])
            ->call('saveRoom')
            ->assertHasErrors('roomForm.amenities.0');
    }

    public function test_room_amenities_are_whitelisted(): void
    {
        $this->hotelDraft();

        $this->fillRoom(Livewire::test(HotelRooms::class))
            ->set('roomForm.amenities', ['wifi', 'elicottero'])
            ->call('saveRoom')
            ->assertHasErrors('roomForm.amenities.1')
            ->assertHasNoErrors('roomForm.amenities.0');
    }

    public function test_room_photo_upload_stored_on_public_disk(): void
    {
        Storage::fake('public');
        $draft = $this->hotelDraft();

        $component = $this->fillRoom(Livewire::test(HotelRooms::class))
            ->set('roomPhotos', [UploadedFile::fake()->image('camera.jpg'), UploadedFile::fake()->image('bagno.jpg')])
            ->call('saveRoom')
            ->assertHasNoErrors();

        $photos = $component->get('form.rooms.0.photos');
        $this->assertCount(2, $photos);

        foreach ($photos as $path) {
            Storage::disk('public')->assertExists($path);
            $this->assertStringStartsWith('structure-photos/', $path);
        }

        $this->assertSame($photos, $draft->fresh()->rooms[0]['photos']);
    }

    public function test_room_photo_must_be_an_image_and_at_most_ten(): void
    {
        Storage::fake('public');
        $this->hotelDraft();

        $this->fillRoom(Livewire::test(HotelRooms::class))
            ->set('roomPhotos', [UploadedFile::fake()->create('menu.pdf', 10, 'application/pdf')])
            ->assertHasErrors('roomPhotos.0');

        $this->fillRoom(Livewire::test(HotelRooms::class))
            ->set('roomPhotos', array_map(fn (int $i) => UploadedFile::fake()->image("c{$i}.jpg"), range(1, 11)))
            ->call('saveRoom')
            ->assertHasErrors('roomPhotos')
            ->assertCount('form.rooms', 0);
    }

    public function test_removing_a_room_photo_deletes_the_unreferenced_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('structure-photos/a.jpg', 'x');
        Storage::disk('public')->put('structure-photos/b.jpg', 'x');
        $draft = $this->hotelDraft(['rooms' => [$this->row('a', ['photos' => ['structure-photos/a.jpg', 'structure-photos/b.jpg']])]]);

        Livewire::test(HotelRooms::class)
            ->call('openRoom', 0)
            ->set('roomForm.photos', ['structure-photos/b.jpg'])
            ->call('saveRoom')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing('structure-photos/a.jpg');
        Storage::disk('public')->assertExists('structure-photos/b.jpg');
        $this->assertSame(['structure-photos/b.jpg'], $draft->fresh()->rooms[0]['photos']);
    }

    /**
     * Una foto della stanza che è anche foto della struttura (o di una stanza
     * pubblicata) resta su disco: la puntano ancora.
     */
    public function test_removing_a_room_keeps_photos_still_referenced(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('structure-photos/shared.jpg', 'x');
        Storage::disk('public')->put('structure-photos/published.jpg', 'x');

        $this->hotelDraft([
            'photos' => ['structure-photos/shared.jpg'],
            'rooms' => [$this->row('a', ['photos' => ['structure-photos/shared.jpg', 'structure-photos/published.jpg']])],
        ]);
        Room::factory()->create(['photos' => ['structure-photos/published.jpg']]);

        Livewire::test(HotelRooms::class)->call('removeRoom', 0);

        Storage::disk('public')->assertExists('structure-photos/shared.jpg');
        Storage::disk('public')->assertExists('structure-photos/published.jpg');
    }

    /** La X dello step foto non cancella una foto che una stanza della bozza usa ancora. */
    public function test_structure_photo_used_by_a_room_survives_the_photo_step_delete(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('structure-photos/shared.jpg', 'x');

        $this->hotelDraft([
            'photos' => ['structure-photos/shared.jpg'],
            'rooms' => [$this->row('a', ['photos' => ['structure-photos/shared.jpg']])],
        ]);

        Livewire::test(HotelPhotos::class)->call('removeSaved', 0);

        Storage::disk('public')->assertExists('structure-photos/shared.jpg');
    }

    /** Un path scritto a mano nel payload non entra nella bozza e non si cancella. */
    public function test_forged_room_photo_path_is_dropped(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('structure-photos/altrui.jpg', 'x');
        $draft = $this->hotelDraft();

        $component = $this->fillRoom(Livewire::test(HotelRooms::class))
            ->set('roomForm.photos', ['structure-photos/altrui.jpg'])
            ->call('saveRoom')
            ->assertSet('form.rooms.0.photos', []);

        $this->assertSame([], $draft->fresh()->rooms[0]['photos']);

        $component->set('form.rooms.0.photos', ['structure-photos/altrui.jpg'])->call('removeRoom', 0);

        Storage::disk('public')->assertExists('structure-photos/altrui.jpg');
    }

    public function test_next_requires_at_least_one_room_and_the_times(): void
    {
        $this->hotelDraft();

        Livewire::test(HotelRooms::class)
            ->call('next')
            ->assertHasErrors(['form.rooms', 'form.checkinFrom', 'form.checkoutTo']);
    }

    public function test_next_saves_rooms_in_draft_with_keys(): void
    {
        $this->hotelDraft();

        $component = $this->fillRoom(Livewire::test(HotelRooms::class))->call('saveRoom');
        $this->fillRoom($component, null, ['name.it' => 'Suite', 'type' => 'suite', 'units' => 1])->call('saveRoom');

        $this->setTimes($component)
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.cancellation'));

        $draft = StructureDraft::latest('id')->first();

        $this->assertSame(5, $draft->current_step);
        $this->assertSame('14:00', $draft->checkin_from);
        $this->assertCount(2, $draft->rooms);
        $this->assertNotSame($draft->rooms[0]['key'], $draft->rooms[1]['key']);
        $this->assertTrue(collect($draft->rooms)->every(fn (array $row) => filled($row['key'])));
        $this->assertSame(['Camera Lago', 'Suite'], array_column(array_column($draft->rooms, 'name'), 'it'));
    }

    public function test_it_rehydrates_legacy_rows_in_the_new_format(): void
    {
        $this->hotelDraft(['checkin_from' => '15:00', 'rooms' => [['type' => 'tripla', 'count' => 4, 'price' => '90']]]);

        $component = Livewire::test(HotelRooms::class)
            ->assertSet('form.checkinFrom', '15:00')
            ->assertSet('form.rooms.0.units', 4)
            ->assertSet('form.rooms.0.max_guests', 3);

        $this->assertNotEmpty($component->get('form.rooms.0.key'));
    }

    /**
     * Casa vacanza: si affitta l'alloggio intero — una sola card, nessun
     * «Aggiungi stanza», unità fissa a 1, ospiti = posti letto.
     *
     * Da partner loggato la bozza dev'essere sua: il wizard non apre quelle
     * altrui.
     */
    private function wholePropertyDraft(?int $userId = null): StructureDraft
    {
        return $this->hotelDraft(['user_id' => $userId, 'type' => 'casa_vacanza']);
    }

    public function test_whole_property_has_single_room_without_add_button(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->wholePropertyDraft($partner->id);

        Livewire::test(HotelRooms::class)
            ->assertSet('form.wholeProperty', true)
            ->assertCount('form.rooms', 1)
            ->assertSet('form.rooms.0.units', 1)
            ->assertSet('form.rooms.0.type', HotelRoomsForm::WHOLE_PROPERTY_TYPE)
            // Le azioni del repeater sono inerti anche se chiamate dal client.
            ->call('openRoom', null)
            ->assertSet('roomModal', false)
            ->call('removeRoom', 0)
            ->assertCount('form.rooms', 1);

        $this->get(route('partner.structure.hotel.rooms'))
            ->assertOk()
            ->assertSee(__('partner.hotel_rooms.whole_heading'))
            ->assertDontSee(__('partner.hotel_rooms.add_room'));
    }

    public function test_whole_property_requires_the_bed_count(): void
    {
        $this->wholePropertyDraft();

        Livewire::test(HotelRooms::class)
            ->call('openRoom', 0)
            ->set('roomForm.price', '120')
            ->set('roomForm.max_guests', '')
            ->call('saveRoom')
            ->assertHasErrors('roomForm.max_guests');
    }

    public function test_whole_property_saves_beds_as_guests_and_advances(): void
    {
        $this->wholePropertyDraft();

        $component = Livewire::test(HotelRooms::class)
            ->call('openRoom', 0)
            ->set('roomForm.max_guests', 6)
            ->set('roomForm.price', '120')
            // Un client che prova a cambiare unità o tipologia non passa.
            ->set('roomForm.units', 4)
            ->set('roomForm.type', 'suite')
            ->call('saveRoom')
            ->assertHasNoErrors();

        $this->setTimes($component)
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.cancellation'));

        $rooms = StructureDraft::latest('id')->first()->rooms;

        $this->assertCount(1, $rooms);
        $this->assertSame(HotelRoomsForm::WHOLE_PROPERTY_TYPE, $rooms[0]['type']);
        $this->assertSame(1, $rooms[0]['units']);
        $this->assertSame(6, $rooms[0]['max_guests']);
        // `beds` resta per le letture legacy (normalizedRooms ne ricava gli ospiti).
        $this->assertSame(6, $rooms[0]['beds']);
    }

    public function test_room_cards_show_the_room_summary(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->hotelDraft(['user_id' => $partner->id, 'rooms' => [$this->row('a', ['name' => ['it' => 'Camera Glicine'], 'price' => '95', 'units' => 4])]]);

        $this->get(route('partner.structure.hotel.rooms'))
            ->assertOk()
            ->assertSee('Camera Glicine')
            ->assertSee(__('partner.hotel_rooms.type_double'))
            ->assertSee(__('partner.hotel_rooms.card_units', ['count' => 4]));
    }
}
