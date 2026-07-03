<?php

namespace App\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;

class ActivityDetail extends Component
{
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
        $entry = collect(Events::EVENTS)->firstWhere('slug', $activity);

        // Solo le attività multi-giorno; gli eventi restano su /eventi/{event}.
        abort_unless($entry !== null && $entry['type'] === 'activity', 404);

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
        // Attività a pagamento: il pill "Partecipa" esiste solo nella variante gratuita,
        // quindi il pop-up partecipa non deve poter aprirsi.
        if (! $this->isFree()) {
            return;
        }

        // TODO: partecipazione reale — per ora mostra solo il pop-up di conferma.
        $this->joinPopupOpen = true;
    }

    public function closeJoinPopup(): void
    {
        $this->joinPopupOpen = false;
    }

    /** Variante gratuita (price "Gratis") — allineata al linguaggio dell'evento gratuito. */
    private function isFree(): bool
    {
        $entry = collect(Events::EVENTS)->firstWhere('slug', $this->activitySlug);

        return ($entry['price'] ?? null) === 'Gratis';
    }

    public function render()
    {
        $activity = collect(Events::EVENTS)->firstWhere('slug', $this->activitySlug);

        // Durata: dalla riga orario "DURATA DI n GIORNI" quando presente, altrimenti il weekend XD di 3 giorni.
        $days = preg_match('/DURATA DI (\d+)/i', $activity['time'] ?? '', $m) ? (int) $m[1] : 3;

        // Prezzo unitario numerico ("118 € a persona" → 118); null per "Gratis"/assente.
        $unitPrice = preg_match('/^(\d+)\s*€/u', $activity['price'] ?? '', $m) ? (int) $m[1] : null;

        return view('livewire.activity-detail', [
            'activity' => $activity,
            // L'XD non definisce un design per le attività gratuite: allineato al linguaggio
            // della variante evento gratuito ("Gratis" corsivo + pill Partecipa, niente riepilogo prezzi).
            'isFree' => $this->isFree(),
            'durationDays' => $days,
            // Testo XD "Durata di 3 giorni, due notti"; per le altre durate la forma numerica.
            'durationLabel' => $days === 3 ? 'Durata di 3 giorni, due notti' : sprintf('Durata di %d giorni, %d notti', $days, $days - 1),
            'priceHeadline' => $activity['price'] ?? 'A partire da 0,00 €',
            'pricePerTwo' => $unitPrice !== null ? $unitPrice.' € per 2 persone' : null,
            'totalPrice' => $unitPrice !== null ? ($unitPrice * 2).' €' : null,
            // "Cosa è incluso" e thread identici all'artboard evento (stessi testi nell'XD).
            'includedColumns' => EventDetail::INCLUDED,
            'threads' => EventDetail::THREADS,
        ])->title('AnimalAmo — '.$activity['title']);
    }
}
