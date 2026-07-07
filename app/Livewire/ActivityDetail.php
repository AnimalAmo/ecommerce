<?php

namespace App\Livewire;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use App\Services\Cart\CartManager;
use App\Services\Pricing\BookingPricingService;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class ActivityDetail extends Component
{
    use HasBookingCalendar;
    use TogglesFavorites;

    /** Slug attività dalla rotta (es. "weekend-escursioni"); il nome differisce dal parametro {activity} per non collidere col binding Livewire. */
    public string $activitySlug = '';

    /** Tab attiva ("informazioni" | "discussione"); deep-linkabile via ?tab=discussione. */
    #[Url(except: 'informazioni')]
    public string $tab = 'informazioni';

    /** Visibilità del pop-up "Aggiunto agli eventi" (XD: "Pop-up evento partecipa", condiviso col dettaglio evento). */
    public bool $joinPopupOpen = false;

    /** Visibilità del pop-up "Aggiunto al carrello" (layout riusato dal dettaglio struttura: nessun pop-up XD dedicato alle attività, DA SEGNALARE). */
    public bool $cartPopupOpen = false;

    /** Campo espanso nel widget: null | 'ospiti' | 'animali' (uno alla volta, come il pop-up del carrello). */
    public ?string $expandedField = null;

    public const TABS = ['informazioni', 'discussione'];

    /** Campi espandibili del widget (le date sono fisse: derivano dalla riga evento). */
    public const FIELDS = ['ospiti', 'animali'];

    public function mount(string $activity): void
    {
        $model = Event::where('slug', $activity)->first();

        // Solo le attività multi-giorno; gli eventi restano su /eventi/{event}.
        abort_unless($model !== null && $model->type === ProductType::Activity, 404);

        $this->activitySlug = $activity;

        // Default del widget come da XD: 2 adulti e 1 animale (specie del primo pet dell'utente, altrimenti cane).
        $this->editGuests = ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0];
        $this->editAnimals = [self::defaultSpecies() => 1];

        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'informazioni';
        }
    }

    /** Apre/chiude un campo del widget; aprirne uno collassa l'altro. */
    public function toggleField(string $field): void
    {
        if (! in_array($field, self::FIELDS, true)) {
            return;
        }

        $this->expandedField = $this->expandedField === $field ? null : $field;
    }

    public function addToCart(): void
    {
        $activity = $this->activity();

        // CTA Partecipa (gratis o senza prezzo): nessun acquisto, il pop-up carrello non deve aprirsi.
        if ($activity->hasJoinCta()) {
            return;
        }

        try {
            // Date NON nelle options: derivano da starts_at/ends_at/duration_days del purchasable.
            app(CartManager::class)->addItem('event', $activity->id, [
                'guests' => $this->editGuests,
                'animals' => $this->editAnimals,
            ], false);
        } catch (CartValidationException $exception) {
            // Violazione disponibilità (es. capienza esaurita): toast danger, niente pop-up.
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return;
        }

        $this->dispatch('cart-updated');
        $this->cartPopupOpen = true;
    }

    public function closeCartPopup(): void
    {
        $this->cartPopupOpen = false;
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
        }
    }

    public function joinEvent(): void
    {
        // Attività a pagamento: il pill "Partecipa" esiste solo nella variante gratuita.
        if (! $this->activity()->hasJoinCta()) {
            return;
        }

        // TODO: partecipazione reale — per ora mostra solo il pop-up di conferma.
        $this->joinPopupOpen = true;
    }

    public function closeJoinPopup(): void
    {
        $this->joinPopupOpen = false;
    }

    private function activity(): Event
    {
        return Event::where('slug', $this->activitySlug)->firstOrFail();
    }

    /** Specie di default dello stepper animali: primo pet dell'utente autenticato, altrimenti cane. */
    private static function defaultSpecies(): string
    {
        return Auth::user()?->pets()->first()?->species ?? 'cane';
    }

    /**
     * Date del widget (NON editabili) derivate dalla riga evento — stessa
     * derivazione della card carrello (CartItemData::dates): fine = ends_at
     * reale, altrimenti durata (3 giorni = start + 2).
     *
     * @return array{checkIn: ?string, checkOut: ?string} dd/mm/YYYY
     */
    private static function widgetDates(Event $activity): array
    {
        if ($activity->starts_at === null) {
            return ['checkIn' => null, 'checkOut' => null];
        }

        return [
            'checkIn' => Format::dateShort($activity->starts_at),
            'checkOut' => match (true) {
                $activity->ends_at !== null => Format::dateShort($activity->ends_at),
                $activity->duration_days !== null && $activity->duration_days > 1 => Format::dateShort($activity->starts_at->clone()->addDays($activity->duration_days - 1)),
                default => null,
            },
        ];
    }

    public function render()
    {
        $activity = $this->activity();

        // Durata: null nel dato = weekend XD di 3 giorni.
        $days = $activity->duration_days ?? 3;

        // Persone reali dagli stepper e totale quotato server-side (a persona; gli animali non sono prezzati).
        $persons = array_sum($this->editGuests);
        $quoteCents = $activity->hasJoinCta()
            ? null
            : app(BookingPricingService::class)->quote($activity, ['guests' => $this->editGuests, 'animals' => $this->editAnimals]);

        return view('livewire.activity-detail', [
            'activity' => $activity,
            // Le attività sono righe Event: alias morph 'event'.
            'isFav' => $this->isFavorite('event', $activity->id),
            // L'XD non definisce un design per le attività gratuite: allineato al linguaggio
            // della variante evento gratuito ("Gratis" corsivo + pill Partecipa, niente riepilogo prezzi).
            'isFree' => $activity->is_free,
            'canJoin' => $activity->hasJoinCta(),
            'durationDays' => $days,
            // Testo XD "Durata di 3 giorni, due notti"; per le altre durate la forma numerica.
            'durationLabel' => $days === 3
                ? __('format.duration_label_weekend')
                : __('format.duration_label', ['days' => $days, 'nights' => $days - 1]),
            'priceHeadline' => $activity->price_cents !== null
                ? __('format.per_person', ['price' => Format::money($activity->price_cents)])
                : __('format.from_price', ['price' => Format::money(0)]),
            // Riepilogo prezzi reale dagli stepper (solo attività acquistabili: !hasJoinCta ⇒ price_cents non null).
            'priceForGuests' => $quoteCents !== null
                ? __('format.for_people', ['price' => Format::money($activity->price_cents), 'count' => $persons])
                : null,
            'totalPrice' => $quoteCents !== null ? Format::money($quoteCents) : null,
            // Date fisse del widget derivate dalla riga evento (starts_at/ends_at/duration_days).
            'dates' => self::widgetDates($activity),
            'guestsLabel' => Format::guests($this->editGuests),
            'animalsLabel' => Format::animals($this->editAnimals),
            'guestsAtMax' => $this->guestsAtMax(),
            'animalsAtMax' => $this->animalsAtMax(),
            'includedColumns' => [$activity->amenityRows('hotel'), $activity->amenityRows('animal')],
            'faqs' => $activity->faqs,
            'threads' => EventDetail::THREADS,
        ])->title('AnimalAmo — '.$activity->title);
    }
}
