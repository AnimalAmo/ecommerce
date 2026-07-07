<?php

namespace App\Livewire\Catalog;

use App\Exceptions\CartValidationException;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Services\Cart\CartManager;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class SmartboxDetail extends Component
{
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

        $options = ['animals' => $this->editAnimals];

        if ($this->gift) {
            $options['gift'] = ['dedication' => null, 'message' => null, 'recipient_email' => null];
        }

        try {
            app(CartManager::class)->addItem('smartbox_package', $box->id, $options, $this->gift);
        } catch (CartValidationException $exception) {
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return;
        }

        $this->dispatch('cart-updated');
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

        return view('livewire.catalog.smartbox-detail', [
            'box' => $box,
            'isFav' => $this->isFavorite('smartbox_package', $box->id),
            'hotelServices' => $box->amenityRows('hotel'),
            'animalServices' => $box->amenityRows('animal'),
            'animalsAtMax' => $this->animalsAtMax(),
        ])->title('AnimalAmo — '.$box->title);
    }
}
