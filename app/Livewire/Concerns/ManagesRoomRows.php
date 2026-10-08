<?php

namespace App\Livewire\Concerns;

use App\Livewire\Forms\HotelRoomsForm;
use App\Services\Partner\Publishing\FamilyPublisher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Elenco di card stanza + modale di modifica, condiviso dallo step 5 del
 * wizard (HotelRooms) e dalla creazione struttura del pannello
 * (StructureCreate). Le righe vivono nel HotelRoomsForm del componente; la
 * modale lavora su una copia (`roomForm`) che entra nelle righe solo con
 * saveRoom(), dopo aver validato.
 *
 * Le foto sono la parte che cambia fra i due: il wizard le scrive subito sul
 * disco e nella bozza (il partner può uscire e ritrovarle), il pannello le
 * tiene come upload temporanei fino al salvataggio della scheda (spec §5.3:
 * niente su disco prima che tutto abbia validato). Da qui i tre hook in fondo.
 *
 * Il componente che lo usa deve usare anche WithFileUploads.
 */
trait ManagesRoomRows
{
    /** Errori della modale: aprirla o chiuderla non tocca quelli del resto della pagina. */
    private const ROOM_ERROR_KEYS = ['roomForm.*', 'roomPhotos', 'roomPhotos.*'];

    /** Indice della riga in modifica; null = stanza nuova. */
    public ?int $editing = null;

    /** Modale aperta. */
    public bool $roomModal = false;

    /** Copia della stanza aperta nella modale (formato riga di HotelRoomsForm). */
    public array $roomForm = [];

    /** Nuove foto della stanza in upload (temporanee Livewire). */
    public array $roomPhotos = [];

    /**
     * Apre la modale su una stanza esistente o, con null, su una nuova.
     * L'alloggio intero ha una riga sola: niente stanze nuove.
     */
    public function openRoom(?int $index = null): void
    {
        $form = $this->roomsForm();
        $rows = $this->baseRoomRows();

        if ($index === null) {
            if ($form->wholeProperty) {
                return;
            }

            $this->roomForm = $form->blankRow();
        } elseif (isset($rows[$index])) {
            $this->roomForm = $form->cleanRow($rows[$index]);
            // Alloggio intero ancora da compilare: posti letto vuoti, non 0.
            $this->roomForm['max_guests'] = $this->roomForm['max_guests'] ?: '';
        } else {
            return;
        }

        $this->editing = $index;
        $this->roomPhotos = [];
        $this->resetValidation(self::ROOM_ERROR_KEYS);
        $this->roomModal = true;
    }

    public function closeRoom(): void
    {
        $this->roomModal = false;
        $this->editing = null;
        $this->roomForm = [];
        $this->roomPhotos = [];
        $this->resetValidation(self::ROOM_ERROR_KEYS);
    }

    public function updatedRoomPhotos(): void
    {
        $this->validate(['roomPhotos.*' => ['image', 'max:8192']]);
    }

    /** Toglie una foto ancora in upload dalla modale. */
    public function removeRoomUpload(int $index): void
    {
        unset($this->roomPhotos[$index]);
        $this->roomPhotos = array_values($this->roomPhotos);
    }

    /** Toglie una foto salvata dalla copia in modale: il file si valuta a saveRoom(). */
    public function removeRoomPhoto(int $index): void
    {
        $photos = $this->roomForm['photos'] ?? [];
        unset($photos[$index]);
        $this->roomForm['photos'] = array_values($photos);
    }

    public function saveRoom(): void
    {
        $form = $this->roomsForm();
        $rows = $this->baseRoomRows();

        if ($this->editing !== null && ! isset($rows[$this->editing])) {
            $this->closeRoom();

            return;
        }

        // Alloggio intero: tipologia e unità non sono scelte del partner.
        if ($form->wholeProperty) {
            $this->roomForm['type'] = HotelRoomsForm::WHOLE_PROPERTY_TYPE;
            $this->roomForm['units'] = 1;
        }

        $rules = ['roomPhotos' => ['array'], 'roomPhotos.*' => ['image', 'max:8192']];
        foreach ($form->roomRules() as $field => $rule) {
            $rules['roomForm.'.$field] = $rule;
        }

        $this->validate($rules, $form->wholeProperty
            ? ['roomForm.max_guests.required' => __('partner.hotel_rooms.beds_error')]
            : []);

        $previous = $this->editing === null ? null : $rows[$this->editing];
        $row = $form->cleanRow($this->roomForm);
        // La chiave la decide il server: nuova per una stanza nuova, quella
        // della riga per una modifica. Una chiave dal client potrebbe
        // duplicarne un'altra (due stanze fuse) o cambiarla (id nuovo a
        // catalogo, occupazione azzerata).
        $row['key'] = $previous === null ? (string) Str::uuid() : $form->cleanRow($previous)['key'];
        // Solo foto che la stanza possiede davvero: un path scritto a mano nel
        // payload non entra (e quindi non si cancellerà mai da qui).
        $row['photos'] = array_values(array_intersect($row['photos'], $this->ownedRoomPhotos($row['key'])));

        if (count($row['photos']) + count($this->roomPhotos) + $this->pendingRoomUploadCount($row['key']) > HotelRoomsForm::MAX_PHOTOS) {
            $this->addError('roomPhotos', __('partner.hotel_rooms.photos_max', ['max' => HotelRoomsForm::MAX_PHOTOS]));

            return;
        }

        $accepted = $this->acceptRoomUploads($row['key'], $this->roomPhotos);
        $row['photos'] = [...$row['photos'], ...$accepted];

        if ($this->editing === null) {
            $rows[] = $row;
        } else {
            $rows[$this->editing] = $row;
        }

        $this->commitRooms($rows, $previous['photos'] ?? [], $accepted);
        $this->closeRoom();
    }

    /** L'alloggio intero non si elimina: è l'unica riga e la sua card resta. */
    public function removeRoom(int $index): void
    {
        $form = $this->roomsForm();
        $rows = $this->baseRoomRows();

        if ($form->wholeProperty || ! isset($rows[$index])) {
            return;
        }

        $removed = $rows[$index];
        unset($rows[$index]);

        $this->forgetRoomUploads((string) ($removed['key'] ?? ''));
        $this->commitRooms(array_values($rows), $removed['photos'] ?? []);
        $this->resetValidation(self::ROOM_ERROR_KEYS);
    }

    /** Sposta una stanza di una posizione (-1 su, +1 giù): l'ordine è quello della scheda. */
    public function moveRoom(int $index, int $direction): void
    {
        $rows = $this->baseRoomRows();
        $target = $index + ($direction < 0 ? -1 : 1);

        if (! isset($rows[$index], $rows[$target])) {
            return;
        }

        [$rows[$index], $rows[$target]] = [$rows[$target], $rows[$index]];

        $this->commitRooms($rows, []);
    }

    /**
     * Scrive le righe nel form; le foto in `$dropped` che nessuna riga usa
     * più si valutano per la cancellazione (solo se la stanza le possedeva).
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $dropped
     * @param  list<string>  $accepted  path appena scritti su disco da questa richiesta
     */
    private function commitRooms(array $rows, array $dropped, array $accepted = []): void
    {
        $form = $this->roomsForm();
        $owned = [...$this->ownedRoomPhotos(null), ...$accepted];

        $rows = array_map(function (array $row) use ($form, $owned): array {
            $row = $form->cleanRow($row);
            $row['photos'] = array_values(array_intersect($row['photos'], $owned));

            return $row;
        }, $rows);

        $form->rooms = $rows;
        $this->persistRooms($rows);

        $stillUsed = array_merge(...array_column($rows, 'photos') ?: [[]]);

        foreach (array_diff(array_intersect($dropped, $owned), $stillUsed) as $path) {
            FamilyPublisher::deletePhotoIfUnreferenced($path);
        }
    }

    /** Form delle stanze del componente (`$form` nel wizard, `$rooms` nel pannello). */
    abstract protected function roomsForm(): HotelRoomsForm;

    /**
     * Righe da cui partono le azioni della modale. Nel wizard sono quelle
     * della bozza, non `form.rooms`: il form è stato pubblico e il client lo
     * riscrive, e una riga manomessa (prezzo negativo, tipologia inventata)
     * finirebbe nella bozza con un moveRoom() senza passare da nessuna regola.
     * Così l'unica riga che entra è quella validata dalla modale.
     *
     * @return list<array<string, mixed>>
     */
    abstract protected function baseRoomRows(): array;

    /**
     * Path sul disco che le stanze possono legittimamente contenere (fonte di
     * verità lato server, mai il payload). `$key` = la stanza in modifica.
     *
     * @return list<string>
     */
    abstract protected function ownedRoomPhotos(?string $key): array;

    /**
     * Le foto appena caricate per la stanza `$key`: torna i path da aggiungere
     * subito alla riga (vuoto se il componente le tiene da parte).
     *
     * @param  list<UploadedFile>  $files
     * @return list<string>
     */
    abstract protected function acceptRoomUploads(string $key, array $files): array;

    /**
     * Salva le righe dove il componente le tiene oltre al form.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    abstract protected function persistRooms(array $rows): void;

    /** Upload temporanei ancora in attesa per la stanza (solo pannello). */
    protected function pendingRoomUploadCount(string $key): int
    {
        return 0;
    }

    /** Dimentica gli upload in attesa di una stanza eliminata (solo pannello). */
    protected function forgetRoomUploads(string $key): void {}
}
