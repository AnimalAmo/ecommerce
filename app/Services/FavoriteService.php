<?php

namespace App\Services;

use App\Enums\OrderPaymentMode;
use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Models\Event\Event;
use App\Models\Favorite\Favorite;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Partner\PartnerPaymentModeService;
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

        return $this->addResolvedToCart($favorite->favoritable_type, $favorite->favoritable, $user);
    }

    /**
     * Aggiunge al carrello un prodotto del catalogo dato per alias morph + id,
     * senza passare da una riga favorites: serve alle card «Le attività più
     * amate» dello stato vuoto del carrello, che mostrano prodotti che il
     * cliente non ha necessariamente tra i preferiti.
     *
     * Difetto C6 (audit 28/09/2026): la borsa di quelle card era solo colore —
     * portava il bottone a giallo e l'aria-label a «Rimuovi dal carrello» senza
     * scrivere niente, mentre la borsa identica di /preferiti aggiungeva
     * davvero. Ora le due passano dalle stesse regole (addResolvedToCart).
     *
     * Ospite ammesso: il suo carrello vive in sessione (CartManager), e senza
     * animali registrati la specie di default è 'cane', come per chi è loggato.
     * Stessi contratti di addToCart(): 400 alias fuori whitelist, 404 prodotto
     * inesistente, false per i non acquistabili, CartValidationException per
     * le violazioni.
     */
    public function addProductToCart(?User $user, string $type, int $id): bool
    {
        // Solo alias della morph map enforced (mai class-string), come toggle().
        abort_unless(in_array($type, self::FAVORITABLE_TYPES, true), 400);

        $product = Relation::getMorphedModel($type)::find($id);

        abort_unless($product !== null, 404);

        return $this->addResolvedToCart($type, $product, $user);
    }

    /**
     * Evento/attività senza posti per ciò che la card impegnerebbe: capienza
     * impostata dal partner (`max_participants`, nullo = illimitata) e posti
     * residui meno delle persone dell'aggiunta rapida.
     *
     * Difetto C5 (audit 28/09/2026): le liste (griglia /eventi, pagina regione,
     * card dei preferiti e dello stato vuoto del carrello) guardavano solo
     * `hasJoinCta()` e la modalità di incasso del titolare, mai i posti. Lo
     * stesso evento offriva la borsa in griglia e la negava aprendone la
     * scheda, e il click rispondeva solo col toast di AvailabilityService.
     *
     * Nelle liste non c'è un numero di ospiti scelto: la soglia sono le
     * persone che la CTA della card impegnerebbe davvero
     * (Event::quickAddPersons(), due adulti per l'aggiunta rapida di
     * un'attività). Con la persona minima un'attività con un posto libero
     * offriva ancora la borsa, e il click la rifiutava. Qui si decide solo cosa
     * disegnare — i posti si contano sotto lock in AvailabilityService, che
     * resta l'autorità anche per una chiamata wire manomessa.
     *
     * Legge solo colonne della riga già caricata: nessuna query.
     *
     * $ownerOnSite: il titolare incassa in struttura, quindi la CTA della card
     * è un rimando alla scheda e non un'aggiunta al carrello. Lì la soglia è
     * la persona minima, come nella scheda (ActivityDetail::isSoldOut()):
     * con i due adulti dell'aggiunta rapida la lista avrebbe detto «posti
     * esauriti» dove la scheda offre i contatti per prenotare.
     */
    public function isSoldOut(Model $product, bool $ownerOnSite = false): bool
    {
        return $product instanceof Event
            && ! $product->hasSeatsFor($ownerOnSite ? 1 : $product->quickAddPersons());
    }

    /**
     * Regole comuni dell'aggiunta dalle card (preferiti e suggerimenti), sul
     * prodotto già risolto: $type = alias morph su cui scrive il carrello.
     */
    private function addResolvedToCart(string $type, Model $product, ?User $user): bool
    {
        // Evento gratuito / "Partecipa" (is_free o senza prezzo): non è acquistabile,
        // come nella griglia eventi — no-op (il bag non è nemmeno mostrato, vedi present()).
        if ($product instanceof Event && $product->hasJoinCta()) {
            return false;
        }

        // Titolare che incassa in struttura: si prenota contattando lui, non da
        // qui (richiesta della cliente, 27/09/2026). present() non disegna
        // nemmeno la borsa, ma Favorites::toggleCart() e
        // Cart::toggleSuggestionCart() arrivano dal payload Livewire: senza
        // questa riga il prodotto entrerebbe in un carrello che
        // al checkout si blocca. Eccezione e non false: false è il no-op
        // silenzioso dei prodotti non acquistabili, qui il cliente merita di
        // sapere perché (il componente la traduce in toast danger).
        if (app(PartnerPaymentModeService::class)->forPurchasable($product) === OrderPaymentMode::OnSite) {
            throw CartValidationException::notPurchasable();
        }

        // Posti esauriti: nessuna guardia qui. La borsa non è disegnata
        // (canAddToCart), ma la regola vera resta di AvailabilityService dentro
        // CartManager, che rifiuta con cart.sold_out anche una chiamata forgiata.
        app(CartManager::class)->addItem(
            $type,
            (int) $product->getKey(),
            $this->defaultCartOptions($product, $user !== null ? self::defaultSpecies($user) : 'cane'),
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
     * metaType, metaText, photo, price, can_add_to_cart, sold_out}.
     *
     * @return list<array<string, mixed>>
     */
    public function cards(User $user): array
    {
        $favorites = $user->favorites()
            ->with('favoritable')
            ->orderBy('id')
            ->get()
            // Prodotti nel frattempo rimossi dal catalogo: card saltata.
            ->filter(fn (Favorite $favorite): bool => $favorite->favoritable !== null);

        $modes = $this->ownerModes($favorites->map(fn (Favorite $favorite): Model => $favorite->favoritable));

        return $favorites
            ->map(fn (Favorite $favorite): array => $this->present($favorite, $modes))
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

    /**
     * Card "Le attività più amate su Animal-Amo" (stato vuoto di carrello e
     * preferiti): i prodotti più messi tra i preferiti, tie-break id crescente.
     * Stesso contratto di card(), ma id = 'alias-id' (unico tra i morph:
     * qui non c'è una riga favorites dell'utente da cui prendere la PK).
     *
     * @return list<array<string, mixed>>
     */
    public function topFavorited(int $limit = 3): array
    {
        // I prodotti si risolvono prima di presentarli: serve l'elenco dei
        // titolari per leggere le modalità di incasso in una query sola.
        $rows = Favorite::query()
            ->select(['favoritable_type', 'favoritable_id'])
            ->selectRaw('count(*) as favorites_count')
            ->groupBy('favoritable_type', 'favoritable_id')
            ->orderByDesc('favorites_count')
            ->orderBy('favoritable_id')
            ->limit($limit)
            ->get()
            ->map(function (Favorite $row): ?array {
                $product = Relation::getMorphedModel($row->favoritable_type)::find($row->favoritable_id);

                // Prodotti nel frattempo rimossi dal catalogo: card saltata.
                return $product !== null ? ['alias' => $row->favoritable_type, 'product' => $product] : null;
            })
            ->filter()
            ->values();

        $modes = $this->ownerModes($rows->pluck('product'));

        return $rows
            ->map(fn (array $row): array => $this->cardFields($row['product']) + [
                'id' => $row['alias'].'-'.$row['product']->getKey(),
                'type' => $row['product']->type->value,
                'favoritable_type' => $row['alias'],
                'favoritable_id' => $row['product']->getKey(),
                // Stessa regola delle card dei preferiti: una borsa che non
                // funziona è peggio di nessuna borsa.
                'can_add_to_cart' => $this->canAddToCart($row['product'], $modes),
                'sold_out' => $this->isSoldOut($row['product'], $this->ownerOnSite($row['product'], $modes)),
                'photo' => $row['product']->imageUrl(),
            ])
            ->all();
    }

    /**
     * Presenta il prodotto preferito nella card, per famiglia (Event / Structure
     * / SmartboxPackage). $modes: le modalità di incasso già lette dei titolari.
     *
     * @param  array<int, OrderPaymentMode>  $modes
     */
    private function present(Favorite $favorite, array $modes): array
    {
        $product = $favorite->favoritable;

        return $this->cardFields($product) + [
            'id' => $favorite->id,
            'type' => $product->type->value,
            // Alias morph + PK del prodotto (dalla riga Favorite): l'aggiunta reale
            // al carrello deriva la famiglia dal ProductType, ma scrive sull'alias.
            'favoritable_type' => $favorite->favoritable_type,
            'favoritable_id' => $favorite->favoritable_id,
            'can_add_to_cart' => $this->canAddToCart($product, $modes),
            // Posti esauriti: la card lo dice al posto della borsa (vedi isSoldOut()).
            'sold_out' => $this->isSoldOut($product, $this->ownerOnSite($product, $modes)),
            // Stessa foto della card listing del prodotto (URL risolto da HasCatalogImages).
            'photo' => $product->imageUrl(),
        ];
    }

    /**
     * Modalità di incasso dei titolari dei prodotti passati, in una query sola
     * (il service si ricorda anche i titolari senza profilo): una lettura per
     * card sarebbe una N+1 dentro la griglia dei preferiti.
     *
     * @param  iterable<Model>  $products
     * @return array<int, OrderPaymentMode>
     */
    private function ownerModes(iterable $products): array
    {
        return app(PartnerPaymentModeService::class)->forOwners(
            collect($products)->map(fn (Model $product): mixed => $product->getAttribute('user_id')),
        );
    }

    /**
     * La borsa si mostra solo se premerla porta davvero a un acquisto: gli eventi
     * gratuiti / "Partecipa" non sono acquistabili, quelli a posti esauriti
     * rifiuterebbero l'aggiunta con un toast (difetto C5, vedi isSoldOut()), e i
     * prodotti di un titolare che incassa in struttura si prenotano
     * contattandolo (richiesta della cliente, 27/09/2026). Una borsa che non
     * funziona è peggio di nessuna borsa.
     *
     * Senza titolare (catalogo mock) o senza profilo partner vale online, come
     * in PartnerPaymentModeService::forOwner().
     *
     * @param  array<int, OrderPaymentMode>  $modes
     */
    private function canAddToCart(Model $product, array $modes): bool
    {
        if ($product instanceof Event && $product->hasJoinCta()) {
            return false;
        }

        if ($this->isSoldOut($product)) {
            return false;
        }

        $owner = $product->getAttribute('user_id');

        return $owner === null
            || ($modes[(int) $owner] ?? OrderPaymentMode::Online) === OrderPaymentMode::Online;
    }

    /**
     * Il titolare del prodotto incassa in struttura? Senza titolare o senza
     * profilo vale online, come in canAddToCart().
     *
     * @param  array<int, OrderPaymentMode>  $modes
     */
    private function ownerOnSite(Model $product, array $modes): bool
    {
        $owner = $product->getAttribute('user_id');

        return $owner !== null && ($modes[(int) $owner] ?? OrderPaymentMode::Online) === OrderPaymentMode::OnSite;
    }

    /** Parte comune della card (titolo, riga pin, riga meta, prezzo), per famiglia di prodotto. */
    private function cardFields(Model $product): array
    {
        return match (true) {
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
            // Adulti da Event::quickAddPersons(), la soglia di isSoldOut().
            ProductType::Activity => [
                'guests' => ['adulti' => $product->quickAddPersons(), 'ragazzi' => 0, 'bambini' => 0],
                'animals' => [$species => 1],
            ],
            // Evento: sempre 1 partecipante (nessun contatore in scheda).
            ProductType::Event => [
                'participants' => $product->quickAddPersons(),
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
