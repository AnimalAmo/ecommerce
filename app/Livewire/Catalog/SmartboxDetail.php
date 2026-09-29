<?php

namespace App\Livewire\Catalog;

use App\Enums\OrderPaymentMode;
use App\Livewire\Concerns\AddsCatalogProductToCart;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Services\Partner\PartnerContacts;
use App\Services\Partner\PartnerPaymentModeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class SmartboxDetail extends Component
{
    use AddsCatalogProductToCart;
    use HasBookingCalendar;
    use TogglesFavorites;

    /** Slug del cofanetto dalla rotta (es. "relax-lombardia"); il nome differisce dal parametro {box} per non collidere col binding Livewire. */
    public string $boxSlug = '';

    /** Toggle Acquista/Regala server-driven: ?regalo=1 preseleziona Regala (deep-link, stesso flag del carrello). */
    #[Url(as: 'regalo', except: false)]
    public bool $gift = false;

    /** Accordion Animali della card aperto/chiuso (unico campo interattivo, stile pop-up carrello). */
    public bool $animalsOpen = false;

    /** Pop-up "Aggiunto al carrello" (layout riusato dal dettaglio struttura — estrapolazione ratificata). */
    public bool $cartPopupOpen = false;

    public function mount(string $box): void
    {
        abort_unless(SmartboxPackage::where('slug', $box)->exists(), 404);

        $this->boxSlug = $box;

        // Stepper animali: default 1 animale, specie = primo pet dell'utente autenticato (altrimenti cane).
        $species = Auth::user()?->pets()->first()?->species ?: 'cane';
        $this->editAnimals = [$species => 1];
    }

    /** Bottoni radio Acquista/Regala della card (visual radio-dot invariato, stato lato server). */
    public function setGift(bool $gift): void
    {
        $this->gift = $gift;
    }

    /** Apre/chiude l'accordion Animali della card. */
    public function toggleAnimals(): void
    {
        $this->animalsOpen = ! $this->animalsOpen;
    }

    /**
     * "Aggiungi al carrello": riga smartbox con is_gift dal toggle; le righe
     * regalo nascono con lo scheletro gift (dedica/messaggio dal carrello,
     * email destinataria dal checkout). Violazione = toast danger.
     */
    public function addToCart(): void
    {
        $box = SmartboxPackage::where('slug', $this->boxSlug)->firstOrFail();

        // Nessuna conversione silenziosa in acquisto per sé: se il partner è
        // passato "in struttura" dopo che la pagina ha mostrato Regala, il
        // regalo è rifiutato col suo messaggio (render() poi riporta la card su
        // Acquista). E senza regalo il rifiuto resta (difetto C7, 28/09/2026):
        // la scheda di chi incassa in struttura non ha il pulsante, quindi la
        // chiamata arriva da un payload forgiato o da uno stato residuo. Una
        // pagina aperta prima del cambio di modalità non arriva qui: il cambio
        // ritira la smartbox (C8) e firstOrFail() qui sopra dà 404, come per
        // una scheda sospesa.
        $options = ['animals' => $this->editAnimals];

        if ($this->gift) {
            $options['gift'] = ['dedication' => null, 'message' => null, 'recipient_email' => null];
        }

        if (! $this->addCatalogProductToCart($box, $options, $this->gift)) {
            return;
        }

        $this->animalsOpen = false;
        $this->cartPopupOpen = true;
    }

    public function closeCartPopup(): void
    {
        $this->cartPopupOpen = false;
    }

    public function render()
    {
        $box = SmartboxPackage::where('slug', $this->boxSlug)->firstOrFail();
        $paysOnSite = $this->paysOnSite($box);

        // ?regalo=1 può arrivare da un link condiviso: per un partner che
        // incassa in struttura il regalo non esiste, si riparte da Acquista.
        if ($paysOnSite) {
            $this->gift = false;
        }

        return view('livewire.catalog.smartbox-detail', [
            'box' => $box,
            // «Vedere tutte le foto»: vuoto con una foto sola, e allora niente pulsante né modale.
            'galleryPhotos' => $box->galleryPhotos(),
            'isFav' => $this->isFavorite('smartbox_package', $box->id),
            'hotelServices' => $box->amenityRows('hotel'),
            'animalServices' => $box->amenityRows('animal'),
            'animalsAtMax' => $this->animalsAtMax(),
            'paysOnSite' => $paysOnSite,
            'contacts' => app(PartnerContacts::class)->forPurchasable($box),
        ])->title('AnimalAmo — '.$box->title);
    }

    /**
     * Il partner incassa in struttura: niente "Regala" e niente pulsante. Lato
     * server le stesse regole le applicano addCatalogProductToCart() e, per il
     * regalo, anche CartManager::addItem.
     */
    private function paysOnSite(SmartboxPackage $box): bool
    {
        return app(PartnerPaymentModeService::class)->forPurchasable($box) === OrderPaymentMode::OnSite;
    }
}
