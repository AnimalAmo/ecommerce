<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\Favorite\Favorite;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Support\Format;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Logica di dominio dei preferiti: toggle persistente, chiavi per idratare i
 * cuori e presentazione delle card. I componenti Livewire restano adapter UI
 * (guardia ospite, cache computed, stato di pagina).
 */
class FavoriteService
{
    /** Alias morph favoritabili (sottoinsieme catalogo della mappa in AppServiceProvider). */
    private const FAVORITABLE_TYPES = ['structure', 'event', 'smartbox_package'];

    /** Aggiunge/toglie il preferito dell'utente ($type = alias morph). */
    public function toggle(User $user, string $type, int $id): void
    {
        // Solo alias della morph map enforced (mai class-string).
        abort_unless(in_array($type, self::FAVORITABLE_TYPES, true), 400);

        // Il prodotto deve esistere: senza FK sulle colonne morph, id arbitrari
        // creerebbero righe orfane (e id negativi farebbero fallire MySQL).
        $model = Relation::getMorphedModel($type);
        abort_unless($model::whereKey($id)->exists(), 404);

        $favorite = $user->favorites()->firstOrCreate([
            'favoritable_type' => $type,
            'favoritable_id' => $id,
        ]);

        if (! $favorite->wasRecentlyCreated) {
            $favorite->delete();
        }
    }

    /**
     * Aggiunge al carrello il preferito dell'utente ($favoriteId = riga favorites):
     * risolve alias morph, prodotto e opzioni di default per famiglia (gli stessi
     * default delle schede di dettaglio), poi delega la scrittura al CartManager.
     * Ritorna false (no-op) per i prodotti non acquistabili; propaga
     * CartValidationException — l'adapter Livewire la traduce in toast danger.
     */
    public function addToCart(User $user, int $favoriteId): bool
    {
        $favorite = $user->favorites()->with('favoritable')->whereKey($favoriteId)->first();

        // Riga inesistente/di un altro utente o prodotto sparito dal catalogo: 404 (mai riga fantasma).
        abort_unless($favorite !== null && $favorite->favoritable !== null, 404);

        // Evento gratuito / "Partecipa" (is_free o senza prezzo): non è acquistabile,
        // come nella griglia eventi — no-op (il bag non è nemmeno mostrato, vedi present()).
        if ($favorite->favoritable instanceof Event && $favorite->favoritable->hasJoinCta()) {
            return false;
        }

        app(CartManager::class)->addItem(
            $favorite->favoritable_type,
            $favorite->favoritable_id,
            $this->defaultCartOptions($favorite->favoritable, self::defaultSpecies($user)),
            false,
        );

        return true;
    }

    /**
     * Chiavi "alias:id" dei preferiti dell'utente per idratare i cuori.
     *
     * @return list<string>
     */
    public function favoritedKeys(User $user): array
    {
        return $user->favorites()
            ->get(['favoritable_type', 'favoritable_id'])
            ->map(fn (Favorite $favorite): string => $favorite->favoritable_type.':'.$favorite->favoritable_id)
            ->all();
    }

    /**
     * Preferiti dell'utente presentati nel contratto della card condivisa
     * (partials/favorite-card): {id = riga favorites, title, location, type,
     * metaType, metaText, photo, price}.
     *
     * @return list<array<string, mixed>>
     */
    public function cards(User $user): array
    {
        return $user->favorites()
            ->with('favoritable')
            ->orderBy('id')
            ->get()
            // Prodotti nel frattempo rimossi dal catalogo: card saltata.
            ->filter(fn (Favorite $favorite): bool => $favorite->favoritable !== null)
            ->map(fn (Favorite $favorite): array => $this->present($favorite))
            ->values()
            ->all();
    }

    /**
     * Tipologie del menu "Tipologia": le tipologie distinte presenti tra le
     * card, in ordine enum (il mock ne mostra 5, qui derivate dai preferiti reali).
     *
     * @return list<ProductType>
     */
    public function availableTypes(array $cards): array
    {
        $present = array_column($cards, 'type');

        return array_values(array_filter(
            ProductType::cases(),
            fn (ProductType $type): bool => in_array($type->value, $present, true),
        ));
    }

    /** Presenta il prodotto preferito nella card, per famiglia (Event / Structure / SmartboxPackage). */
    private function present(Favorite $favorite): array
    {
        $product = $favorite->favoritable;

        $card = match (true) {
            $product instanceof Event => [
                'title' => $product->title,
                'location' => $product->location,
                // Stessa logica della riga orario della griglia eventi; la card
                // non ha l'uppercase via CSS, quindi maiuscole qui.
                ...($product->type === ProductType::Activity && $product->duration_days
                    ? ['metaType' => 'durata', 'metaText' => mb_strtoupper(__('format.duration_days', ['days' => $product->duration_days]))]
                    : ['metaType' => 'data', 'metaText' => $product->starts_at ? Format::eventTime($product->starts_at) : '']),
                // Gli eventi non hanno price_from: prezzo pieno (0 per i gratuiti, come il mock).
                'price' => Format::money($product->price_cents ?? 0),
            ],
            $product instanceof Structure => [
                'title' => $product->name,
                'location' => $product->location,
                // Stessa riga rating della griglia regione (colonna rating);
                // strutture partner senza recensioni: stato "Nuovo".
                'metaType' => 'rating',
                'metaText' => $product->rating !== null ? Format::rating($product->rating) : __('holiday.new'),
                'price' => Format::money($product->price_from_cents),
            ],
            // SmartboxPackage: niente località né data — la riga pin mostra
            // l'audience (null per i box partner) e la riga durata la validità
            // del cofanetto (analogo più vicino, via Format::validity).
            default => [
                'title' => $product->title,
                'location' => $product->audience ?? '',
                'metaType' => 'durata',
                'metaText' => mb_strtoupper(__('format.valid_for', ['validity' => Format::validity($product->validity_months)])),
                'price' => Format::money($product->price_from_cents),
            ],
        };

        return $card + [
            'id' => $favorite->id,
            'type' => $product->type->value,
            // Alias morph + PK del prodotto (dalla riga Favorite): l'aggiunta reale
            // al carrello deriva la famiglia dal ProductType, ma scrive sull'alias.
            'favoritable_type' => $favorite->favoritable_type,
            'favoritable_id' => $favorite->favoritable_id,
            // Eventi gratuiti / "Partecipa" non sono acquistabili: niente bottone borsa.
            'can_add_to_cart' => ! ($product instanceof Event && $product->hasJoinCta()),
            // Stessa foto della card listing del prodotto (URL risolto da HasCatalogImages).
            'photo' => $product->imageUrl(),
        ];
    }

    /**
     * Opzioni di default del carrello per il prodotto preferito, per famiglia
     * (stessi default dei widget di dettaglio). La famiglia è derivata dal
     * ProductType del prodotto — service/activity distinguono la variante,
     * l'alias morph resta structure/event/smartbox_package.
     *
     * @return array<string, mixed>
     */
    private function defaultCartOptions(Model $product, string $species): array
    {
        $today = new DateTimeImmutable('today');

        return match ($product->type) {
            // Servizio (riga Structure): giorno singolo oggi+7, fascia 10:00–16:00, 1 animale.
            ProductType::Service => [
                'animals' => [$species => 1],
                'day' => $today->modify('+7 days')->format('Y-m-d'),
                'time_from' => '10:00',
                'time_to' => '16:00',
            ],
            // Attività (riga Event): 2 adulti + 1 animale; le date derivano dalla riga evento.
            ProductType::Activity => [
                'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
                'animals' => [$species => 1],
            ],
            // Evento: sempre 1 partecipante (nessun contatore in scheda).
            ProductType::Event => [
                'participants' => 1,
            ],
            // Hotel (riga Structure): soggiorno oggi+7 → oggi+12, 2 adulti, 1 animale.
            ProductType::Structure => [
                'check_in' => $today->modify('+7 days')->format('Y-m-d'),
                'check_out' => $today->modify('+12 days')->format('Y-m-d'),
                'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
                'animals' => [$species => 1],
            ],
            // Smartbox (stay/wellness/adventure): solo animali, niente regalo.
            default => [
                'animals' => [$species => 1],
            ],
        };
    }

    /** Specie preselezionata dello stepper animali: primo pet dell'utente, altrimenti 'cane'. */
    private static function defaultSpecies(User $user): string
    {
        $species = mb_strtolower(trim((string) $user->pets()->first()?->species));

        return $species !== '' ? $species : 'cane';
    }
}
