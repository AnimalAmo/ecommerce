<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\Favorite\Favorite;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Support\Format;
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
                // Stessa riga rating della griglia regione (colonna rating).
                'metaType' => 'rating',
                'metaText' => Format::rating($product->rating),
                'price' => Format::money($product->price_from_cents),
            ],
            // SmartboxPackage: niente località né data — la riga pin mostra
            // l'audience (come la sua card listing) e la riga durata la validità
            // del cofanetto (analogo più vicino, via Format::validity).
            default => [
                'title' => $product->title,
                'location' => $product->audience,
                'metaType' => 'durata',
                'metaText' => mb_strtoupper(__('format.valid_for', ['validity' => Format::validity($product->validity_months)])),
                'price' => Format::money($product->price_from_cents),
            ],
        };

        return $card + [
            'id' => $favorite->id,
            'type' => $product->type->value,
            // Stessa foto della card listing del prodotto (colonna img, asset img/xd/).
            'photo' => $product->img.'.jpg',
        ];
    }
}
