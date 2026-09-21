<?php

namespace App\Services\Content;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\Faq\Faq;
use App\Models\Structure\Structure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Domande frequenti: quelle della pagina di assistenza (divise per argomento)
 * e quelle agganciate a una scheda del catalogo (polimorfiche: struttura o
 * evento/attività). Un "gruppo" è l'insieme in cui vale l'ordine: l'argomento
 * per le prime, la scheda per le seconde.
 *
 * Le schede si leggono senza scope globali: il pannello deve poter scrivere
 * anche sulle domande di una scheda sospesa o in attesa di approvazione.
 */
class FaqService
{
    public const PLATFORM_FLAG_KEY = 'faqs.platform_exists';

    /** Famiglie di schede che hanno domande: alias del morph map => classe. */
    public const PRODUCT_TYPES = [
        'structure' => Structure::class,
        'event' => Event::class,
    ];

    /** @return array<string, Collection<int, Faq>> argomento => domande, nell'ordine della pagina */
    public function platformGroups(): array
    {
        $faqs = Faq::query()->platform()->orderBy('position')->orderBy('id')->get()->groupBy('topic');

        $groups = [];

        foreach (Faq::TOPICS as $topic) {
            $groups[$topic] = $faqs->get($topic, collect())->values();
        }

        return $groups;
    }

    /**
     * Le domande delle schede, una voce per scheda.
     *
     * @return Collection<int, array{key: string, label: string, family: string, url: string|null, faqs: Collection<int, Faq>}>
     */
    public function productGroups(): Collection
    {
        $faqs = Faq::query()
            ->forProducts()
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        // Le schede si caricano a mano, per tipo e senza scope globali: una
        // morphTo passerebbe dallo scope di visibilità del catalogo, e le
        // domande di una scheda sospesa resterebbero senza nome.
        $products = [];

        foreach ($faqs->groupBy('faqable_type') as $type => $group) {
            $class = self::PRODUCT_TYPES[$type] ?? null;

            if ($class !== null) {
                $products[$type] = $class::query()->withoutGlobalScopes()
                    ->whereKey($group->pluck('faqable_id')->unique()->all())
                    ->get()
                    ->keyBy(fn (Model $product) => $product->getKey());
            }
        }

        return $faqs
            ->groupBy(fn (Faq $faq): string => $faq->faqable_type.':'.$faq->faqable_id)
            ->map(function (Collection $faqs, string $key) use ($products): array {
                $first = $faqs->first();
                $product = $products[$first->faqable_type][$first->faqable_id] ?? null;

                return [
                    'key' => $key,
                    'label' => $product === null ? $key : $this->productLabel($product),
                    'family' => (string) $first->faqable_type,
                    'url' => $product === null ? null : $this->productUrl($product),
                    'faqs' => $faqs->values(),
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Le schede a cui si può agganciare una domanda, per il menu dell'editor.
     *
     * @return array<string, string> "structure:12" => nome
     */
    public function productOptions(): array
    {
        $options = [];

        foreach (self::PRODUCT_TYPES as $alias => $class) {
            $class::query()->withoutGlobalScopes()->orderBy('id')->get()
                ->each(function (Model $product) use (&$options, $alias): void {
                    $options[$alias.':'.$product->getKey()] = $this->productLabel($product);
                });
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    /**
     * Crea o aggiorna una domanda. Una domanda che cambia gruppo (argomento o
     * scheda) va in fondo al nuovo; una nuova va in fondo al suo.
     *
     * @param  array{topic?: string|null, product?: string|null, question: array<string, string|null>, answer: array<string, string|null>}  $data
     */
    public function save(?Faq $faq, array $data): Faq
    {
        $faq ??= new Faq;

        [$type, $id, $topic] = $this->target($data);

        $moved = ! $faq->exists
            || $faq->faqable_type !== $type
            || (int) $faq->faqable_id !== (int) $id
            || $faq->topic !== $topic;

        $faq->faqable_type = $type;
        $faq->faqable_id = $id;
        $faq->topic = $topic;

        foreach (['question', 'answer'] as $attribute) {
            foreach (['it', 'en'] as $locale) {
                $value = trim((string) ($data[$attribute][$locale] ?? ''));
                $value === '' ? $faq->forgetTranslation($attribute, $locale) : $faq->setTranslation($attribute, $locale, $value);
            }
        }

        if ($moved) {
            $faq->position = min(255, (int) $this->groupQuery($type, $id, $topic)->whereKeyNot($faq->id ?? 0)->max('position') + 1);
        }

        $faq->save();

        return $faq;
    }

    public function delete(Faq $faq): void
    {
        DB::transaction(function () use ($faq): void {
            $faq->delete();
            $this->renumber($faq->faqable_type, $faq->faqable_id, $faq->topic);
        });
    }

    /** Sposta una domanda alla posizione data (0 = prima) dentro il suo gruppo. */
    public function move(Faq $faq, int $position): void
    {
        DB::transaction(function () use ($faq, $position): void {
            $siblings = $this->groupQuery($faq->faqable_type, $faq->faqable_id, $faq->topic)
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->reject(fn (Faq $sibling): bool => $sibling->is($faq))
                ->values();

            $position = max(0, min($position, $siblings->count()));
            $siblings->splice($position, 0, [$faq]);

            $this->persistOrder($siblings);
        });
    }

    /** C'è almeno una domanda per la pagina pubblica? In cache: lo chiede il piede di ogni pagina. */
    public function hasPlatformFaqs(): bool
    {
        return Cache::rememberForever(self::PLATFORM_FLAG_KEY, fn (): bool => Faq::query()->platform()->exists());
    }

    public static function forgetPlatformFlag(): void
    {
        Cache::forget(self::PLATFORM_FLAG_KEY);
    }

    public function productLabel(Model $product): string
    {
        return (string) ($product instanceof Structure ? $product->name : $product->title);
    }

    /** Scheda pubblica della struttura o dell'evento (stesse rotte del catalogo). */
    public function productUrl(Model $product): ?string
    {
        if ($product instanceof Event) {
            return $product->type === ProductType::Activity
                ? route('eventi.activity', ['activity' => $product->slug])
                : route('eventi.detail', ['event' => $product->slug]);
        }

        if (! $product instanceof Structure || blank($product->region?->slug)) {
            return null;
        }

        return $product->type === ProductType::Service
            ? route('holiday.service', ['region' => $product->region->slug, 'service' => $product->slug])
            : route('holiday.structure', ['region' => $product->region->slug, 'structure' => $product->slug]);
    }

    /**
     * @param  array{topic?: string|null, product?: string|null}  $data
     * @return array{0: string|null, 1: int|null, 2: string|null} tipo, id, argomento
     */
    private function target(array $data): array
    {
        $product = (string) ($data['product'] ?? '');

        if ($product === '') {
            $topic = (string) ($data['topic'] ?? '');

            if (! in_array($topic, Faq::TOPICS, true)) {
                throw new InvalidArgumentException(__('admin-content.faq.invalid_target'));
            }

            return [null, null, $topic];
        }

        [$type, $id] = array_pad(explode(':', $product, 2), 2, '');
        $class = self::PRODUCT_TYPES[$type] ?? null;

        if ($class === null || ! $class::query()->withoutGlobalScopes()->whereKey((int) $id)->exists()) {
            throw new InvalidArgumentException(__('admin-content.faq.invalid_target'));
        }

        return [$type, (int) $id, null];
    }

    /** @return Builder<Faq> */
    private function groupQuery(?string $type, ?int $id, ?string $topic): Builder
    {
        return $type === null
            ? Faq::query()->platform()->where('topic', $topic)
            : Faq::query()->where('faqable_type', $type)->where('faqable_id', $id);
    }

    private function renumber(?string $type, ?int $id, ?string $topic): void
    {
        $this->persistOrder($this->groupQuery($type, $id, $topic)->orderBy('position')->orderBy('id')->get());
    }

    /** @param  Collection<int, Faq>  $faqs */
    private function persistOrder(Collection $faqs): void
    {
        foreach ($faqs->values() as $index => $faq) {
            if ($faq->position !== $index + 1) {
                $faq->position = min(255, $index + 1);
                $faq->save();
            }
        }
    }
}
