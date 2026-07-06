<?php

namespace App\Livewire;

use App\Enums\ProductType;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use App\Support\Format;
use Livewire\Attributes\Url;
use Livewire\Component;

class ActivityDetail extends Component
{
    use TogglesFavorites;

    /** Slug attività dalla rotta (es. "weekend-escursioni"); il nome differisce dal parametro {activity} per non collidere col binding Livewire. */
    public string $activitySlug = '';

    /** Tab attiva ("informazioni" | "discussione"); deep-linkabile via ?tab=discussione. */
    #[Url(except: 'informazioni')]
    public string $tab = 'informazioni';

    /** Visibilità del pop-up "Aggiunto agli eventi" (XD: "Pop-up evento partecipa", condiviso col dettaglio evento). */
    public bool $joinPopupOpen = false;

    public const TABS = ['informazioni', 'discussione'];

    public function mount(string $activity): void
    {
        $model = Event::where('slug', $activity)->first();

        // Solo le attività multi-giorno; gli eventi restano su /eventi/{event}.
        abort_unless($model !== null && $model->type === ProductType::Activity, 404);

        $this->activitySlug = $activity;

        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'informazioni';
        }
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

    public function render()
    {
        $activity = $this->activity();

        // Durata: null nel dato = weekend XD di 3 giorni.
        $days = $activity->duration_days ?? 3;

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
            'pricePerTwo' => $activity->price_cents !== null
                ? __('format.for_people', ['price' => Format::money($activity->price_cents), 'count' => 2])
                : null,
            'totalPrice' => $activity->price_cents !== null ? Format::money($activity->price_cents * 2) : null,
            'includedColumns' => [$activity->amenityRows('hotel'), $activity->amenityRows('animal')],
            'faqs' => $activity->faqs,
            'threads' => EventDetail::THREADS,
        ])->title('AnimalAmo — '.$activity->title);
    }
}
