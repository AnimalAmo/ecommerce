<?php

namespace App\Services\Partner\Publishing;

use App\Models\Amenity\Amenity;
use App\Models\Event\Event;
use App\Models\OrderItem\OrderItem;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Support\Translations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Base dei publisher di famiglia: ogni publish() fa updateOrCreate con chiave
 * `structure_draft_id` (unique sulle tabelle catalogo), così ri-pubblicare lo
 * stesso draft aggiorna la riga esistente invece di duplicarla. Qui vivono le
 * mappature comuni draft → catalogo (traduzioni, slug, foto, prezzi, amenities,
 * righe general_info) condivise dalle tre famiglie.
 */
abstract class FamilyPublisher
{
    /**
     * Slug del wizard → nome Amenity (AmenitySeeder). Gli slug senza equivalente
     * a catalogo (riscaldamento, tv, area_animali, ...) vengono ignorati; le
     * amenity del gruppo non selezionate finiscono nel pivot con included=false
     * (le righe ✗ del template).
     */
    protected const AMENITY_MAP = [
        'wifi' => 'Wifi',
        'aria_condizionata' => 'Aria condizionata negli spazi comuni',
        'sauna' => 'Spa', // approssimazione: il wizard non ha una voce "Spa" propria
        'spa' => 'Spa',
        'pranzo' => 'Pranzo',
        'pet_sitting' => 'Pet sitting',
        'veterinario' => 'Servizio veterinario',
        'omaggio' => 'Omaggio di benvenuto',
    ];

    /**
     * Righe a catalogo che possono puntare a una foto del wizard, con le
     * colonne immagine di ciascuna. Tutte e tre le famiglie, non solo quella
     * della bozza: un cambio di ramo (F5) può lasciare a catalogo la riga
     * della famiglia precedente, che punta ancora alla sua copertina.
     */
    private const CATALOG_IMAGE_COLUMNS = [
        Structure::class => ['img', 'hero_img', 'map_img'],
        Event::class => ['img', 'hero_img'],
        SmartboxPackage::class => ['img', 'hero_img'],
    ];

    abstract public function publish(StructureDraft $draft): Model;

    /**
     * Cancella dal disco public una foto del wizard, ma solo se nessuna pagina
     * la mostra più. Qui passano tutte le cancellazioni di foto del wizard: la
     * X dello step foto (HandlesPhotoUploads::removeSaved) e la potatura della
     * vecchia copertina dopo una ripubblicazione (vedi coverPhoto()).
     *
     * Difetto F1 (audit 27/09/2026): la X cancellava il file nello stesso clic
     * in cui lo toglieva dalla bozza, mentre `img`/`hero_img` della riga a
     * catalogo lo puntavano ancora — il publisher le riscrive solo alla
     * ripubblicazione, e HasCatalogImages::resolveImage() non controlla che il
     * file esista. La scheda pubblica serviva un'immagine rotta, e se il
     * partner abbandonava la modifica (magari bloccato dal minimo di foto) il
     * file era perso per sempre.
     *
     * Il criterio è il riferimento al file, non lo stato della bozza: `status`
     * non dice con certezza se esiste una riga a catalogo (una modifica in
     * attesa di Stripe resta `completed` con la versione precedente online, un
     * cambio di ramo lascia la riga dell'altra famiglia). Si guardano quindi
     * tutte le righe a catalogo, anche nascoste o sospese, più le righe
     * d'ordine: `order_items.photo_url` fotografa l'URL della copertina al
     * momento dell'acquisto (CartItemData::photoUrl) e lo mostrano lo storico
     * del cliente e il dettaglio prenotazione del partner. E le foto di ogni
     * bozza: chi chiama ha già tolto il path dalla propria, quindi se un'altra
     * bozza lo contiene ancora il file è suo, e cancellarlo le toglierebbe una
     * foto che nessuna riga a catalogo protegge (le non-copertina vivono solo
     * lì).
     *
     * Nel dubbio il file resta: meglio un orfano su disco che una scheda rotta.
     *
     * @return bool true se il file è stato cancellato
     */
    public static function deletePhotoIfUnreferenced(string $path): bool
    {
        if (blank($path) || self::isPhotoReferenced($path)) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    /** Il path è ancora puntato da una bozza, da una riga a catalogo o da una riga d'ordine? */
    private static function isPhotoReferenced(string $path): bool
    {
        // whereJsonContains e non un LIKE: il cast array di Laravel serializza
        // `/` come `\/`, quindi il path grezzo non compare nel testo della colonna.
        if (StructureDraft::query()->whereJsonContains('photos', $path)->exists()) {
            return true;
        }

        foreach (self::CATALOG_IMAGE_COLUMNS as $model => $columns) {
            $referenced = $model::withHidden()
                ->where(function (Builder $query) use ($columns, $path): void {
                    foreach ($columns as $column) {
                        $query->orWhere($column, $path);
                    }
                })
                ->exists();

            if ($referenced) {
                return true;
            }
        }

        // photo_url è un URL completo (Storage::url del path), quindi si
        // confronta la coda. Un falso positivo tiene il file: è il verso sicuro.
        return OrderItem::query()->where('photo_url', 'like', '%'.$path)->exists();
    }

    /**
     * Colonne di moderazione da aggiungere all'updateOrCreate (config/admin.php).
     *
     * Moderazione spenta: niente, la riga nasce `approved` dal default di colonna.
     * Accesa: una riga nuova, o rimandata con "Chiedi modifiche", va in attesa;
     * una già approvata o già in attesa non cambia stato.
     *
     * @return array<string, mixed>
     */
    protected function moderationAttributes(?Model $current): array
    {
        if (! config('admin.moderation')) {
            return [];
        }

        if ($current === null || $current->approval_status === Structure::APPROVAL_CHANGES_REQUESTED) {
            return [
                'approval_status' => Structure::APPROVAL_PENDING,
                'approval_requested_at' => now(),
            ];
        }

        return [];
    }

    /**
     * Traduzioni compilate del campo draft, da assegnare alla colonna
     * translatable del catalogo. Mai vuoto: le colonne testo sono NOT NULL.
     *
     * Le lingue che la bozza non ha più arrivano a null (Translations::replacing):
     * la riga di catalogo esiste già a ogni ripubblicazione, e spatie non
     * toglie le lingue che non riceve. Difetto W5 (tester, 28/09/2026): il
     * wizard toglieva ormai la traduzione inglese dalla bozza, ma la scheda su
     * /en continuava a mostrare quella vecchia.
     */
    protected function translations(StructureDraft $draft, string $field): array
    {
        $values = array_filter($draft->getTranslations($field), fn ($value) => filled($value));

        // Tutto vuoto: '' in italiano per le colonne NOT NULL, e le altre lingue
        // a null anche qui, o un testo inglese tolto (un «Altro» deselezionato)
        // resterebbe sulla scheda /en.
        return $values === [] ? ['it' => ''] + Translations::replacing([]) : Translations::replacing($values);
    }

    /** Slug unico e stabile: nome it + id draft (events/smartbox hanno unique index). */
    protected function slug(StructureDraft $draft, string $fallback): string
    {
        $base = Str::slug($draft->getTranslation('name', 'it')) ?: $fallback;

        return $base.'-'.$draft->id;
    }

    /**
     * Prima foto caricata nel wizard (path sul disco public: HasCatalogImages
     * la risolve in Storage URL). '' per soddisfare le colonne img NOT NULL:
     * blank ⇒ gli accessor tornano null e i blade nascondono/degradano.
     *
     * È anche il punto in cui la vecchia copertina esce dal catalogo: vedi
     * pruneReplacedCovers().
     */
    protected function coverPhoto(StructureDraft $draft): string
    {
        $this->pruneReplacedCovers($draft);

        return $draft->photos[0] ?? '';
    }

    /**
     * Difetto F1: la X dello step foto non cancella più un file che la scheda
     * pubblicata punta ancora (deletePhotoIfUnreferenced()); quel file va
     * cancellato qui, quando la nuova versione della scheda è a catalogo.
     *
     * Chiamata da coverPhoto(), cioè mentre il publisher compone l'updateOrCreate:
     * la riga a catalogo ha ancora la copertina vecchia, ed è da lì che si
     * legge quale file sta per essere sostituito. Se non è più tra le foto
     * della bozza, la cancellazione si prenota con DB::afterCommit(): parte solo
     * quando la transazione di DraftCompleter ha scritto la riga nuova, e
     * sparisce con un rollback (publish fallito a metà) — la riga resta com'era
     * e il suo file con lei. Un partner non pagabile non arriva nemmeno qui:
     * DraftPublisher lo ferma prima del publisher di famiglia, la riga tiene la
     * versione precedente e il file resta finché AwaitingDraftPublisher non
     * ripubblica.
     *
     * Nessuna riga a catalogo per questa bozza (prima pubblicazione) ⇒ niente
     * da potare. Senza transazione aperta afterCommit() esegue subito, prima
     * della scrittura: il controllo dentro deletePhotoIfUnreferenced() vede
     * ancora la riga vecchia e lascia il file (orfano, non rotto). In
     * produzione ogni pubblicazione passa da DraftCompleter, che apre la
     * transazione.
     *
     * coverPhoto() è chiamata due volte per pubblicazione (img e hero_img): la
     * seconda prenotazione trova il file già cancellato, ed è innocua.
     */
    private function pruneReplacedCovers(StructureDraft $draft): void
    {
        if ($draft->getKey() === null) {
            return;
        }

        $kept = $draft->photos ?? [];

        $replaced = collect(array_keys(self::CATALOG_IMAGE_COLUMNS))
            ->flatMap(fn (string $model) => $model::withHidden()
                ->where('structure_draft_id', $draft->getKey())
                ->get(['img', 'hero_img'])
                ->flatMap(fn (Model $row) => [$row->img, $row->hero_img]))
            // Solo upload sul disco public: gli stem del template XD (senza '/')
            // sono asset versionati, non file del partner.
            ->filter(fn (?string $path) => filled($path) && str_contains($path, '/'))
            ->reject(fn (string $path) => in_array($path, $kept, true))
            ->unique();

        foreach ($replaced as $path) {
            DB::afterCommit(fn () => self::deletePhotoIfUnreferenced($path));
        }
    }

    /** cancellation_when del wizard ('30'|'15'|'7'|'1') → giorni interi. */
    protected function cancellationDays(StructureDraft $draft): ?int
    {
        return $draft->cancellation_when !== null ? (int) $draft->cancellation_when : null;
    }

    /**
     * Prezzo inserito dal partner → integer cents (il B2C fa aritmetica sui
     * cents, Format::money è type-hinted int). I valori già numeric (la regola
     * del wizard) passano diretti — il cleanup regex mangerebbe l'esponente di
     * '1e3'; per il resto normalizza la virgola italiana e ripulisce i valori
     * legacy tipo '215 €'.
     */
    protected function cents(?string $value): int
    {
        $value = trim((string) $value);

        if (! is_numeric($value)) {
            $value = str_replace(',', '.', preg_replace('/[^\d,.]/', '', $value));
        }

        return (int) round(((float) $value) * 100);
    }

    /** Riga general_info della cancellazione gratuita (icona calendar-return). */
    protected function cancellationRow(StructureDraft $draft): ?array
    {
        $days = $this->cancellationDays($draft);

        if ($days === null) {
            return null;
        }

        return [
            'icon' => 'calendar-return',
            'title' => 'Cancellazione gratuita',
            'lines' => [$days === 1 ? "Fino a 1 giorno prima dell'arrivo" : "Fino a {$days} giorni prima dell'arrivo"],
        ];
    }

    /**
     * Righe general_info dei pasti: colazione → icona coffee, pranzo/cena →
     * icona lunch, con gli orari di meal_times quando compilati.
     *
     * @param  list<string>  $meals  slug selezionati tra colazione|pranzo|cena
     * @param  array<string, array{from?: string, to?: string}>  $mealTimes
     */
    protected function mealRows(array $meals, array $mealTimes): array
    {
        $time = function (string $meal) use ($mealTimes): ?string {
            $slot = $mealTimes[$meal] ?? [];

            return filled($slot['from'] ?? null) && filled($slot['to'] ?? null)
                ? $slot['from'].'-'.$slot['to']
                : null;
        };

        $rows = [];

        if (in_array('colazione', $meals, true)) {
            $rows[] = [
                'icon' => 'coffee',
                'title' => 'Colazione inclusa',
                'lines' => array_values(array_filter([$time('colazione') ? 'Orario: '.$time('colazione') : null])),
            ];
        }

        $lunch = in_array('pranzo', $meals, true);
        $dinner = in_array('cena', $meals, true);

        if ($lunch || $dinner) {
            $rows[] = [
                'icon' => 'lunch',
                'title' => $lunch && $dinner ? 'Pranzo e cena inclusi' : ($lunch ? 'Pranzo incluso' : 'Cena inclusa'),
                'lines' => array_values(array_filter([
                    $lunch && $time('pranzo') ? ($dinner ? 'Orario pranzo: ' : 'Orario: ').$time('pranzo') : null,
                    $dinner && $time('cena') ? ($lunch ? 'Orario cena: ' : 'Orario: ').$time('cena') : null,
                ])),
            ];
        }

        return $rows;
    }

    /**
     * Sincronizza il pivot amenityables riproducendo la semantica del template:
     * per OGNI amenity dei due gruppi una riga, included=true se selezionata
     * nel wizard (✓ verde), false altrimenti (✗ rosa). Posizioni 1..n per
     * gruppo, come AmenitySeeder::pivot().
     *
     * @param  list<string>  $selectedSlugs  slug wizard selezionati (tutte le colonne json rilevanti)
     */
    protected function syncAmenities(Model $model, array $selectedSlugs): void
    {
        $names = array_values(array_intersect_key(self::AMENITY_MAP, array_flip($selectedSlugs)));

        $payload = [];

        foreach ([Amenity::GROUP_HOTEL, Amenity::GROUP_ANIMAL] as $group) {
            $position = 0;

            foreach (Amenity::query()->where('group', $group)->orderBy('id')->get() as $amenity) {
                $payload[$amenity->id] = [
                    'included' => in_array($amenity->name, $names, true),
                    'position' => ++$position,
                ];
            }
        }

        $model->amenities()->sync($payload);
    }
}
