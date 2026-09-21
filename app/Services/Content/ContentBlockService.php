<?php

namespace App\Services\Content;

use App\Models\Content\ContentBlock;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Testi delle pagine pubbliche riscritti dal pannello (tabella content_blocks)
 * sopra i file lingua. Il sito legge da cms(): se per la lingua corrente c'è
 * un testo della cliente vale quello, altrimenti il file lingua. Nessuna
 * migrazione di massa e nessuna pagina vuota: dove non si scrive niente resta
 * il testo originale.
 *
 * Tutti i blocchi stanno in una sola mappa in cache (sono poche decine di
 * righe), invalidata a ogni scrittura dal model. Scoped: in una request la
 * mappa si legge una volta sola anche se la pagina chiama cms() venti volte.
 */
#[Scoped]
class ContentBlockService
{
    public const CACHE_KEY = 'content_blocks.map';

    public const LOCALES = ['it', 'en'];

    /** @var array<string, array<string, string>>|null chiave => [lingua => testo] */
    private ?array $map = null;

    /** @return array<string, array{route: string, blocks: array<string, string>}> sezione => rotta e chiave => tipo */
    public function sections(): array
    {
        return config('admin-content.sections', []);
    }

    /** @return array{route: string, blocks: array<string, string>}|null */
    public function section(string $section): ?array
    {
        return $this->sections()[$section] ?? null;
    }

    /** Nome della sezione nel pannello. */
    public function sectionLabel(string $section): string
    {
        return __('admin-content.sections.'.$section);
    }

    /** Etichetta del campo di una chiave nel pannello. */
    public function blockLabel(string $key): string
    {
        return __('admin-content.site_blocks.'.$key);
    }

    /** @return array<string, array<string, string>> */
    public function map(): array
    {
        return $this->map ??= Cache::rememberForever(self::CACHE_KEY, fn (): array => ContentBlock::query()
            ->get(['key', 'value'])
            ->mapWithKeys(fn (ContentBlock $block): array => [$block->key => $block->getTranslations('value')])
            ->all());
    }

    public function forget(): void
    {
        $this->map = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** Il testo della cliente per la lingua, o null se non ha scritto niente. */
    public function override(string $key, ?string $locale = null): ?string
    {
        $value = trim((string) ($this->map()[$key][$locale ?? app()->getLocale()] ?? ''));

        return $value === '' ? null : $value;
    }

    /** @param  array<string, mixed>  $replace */
    public function text(string $key, array $replace = []): string
    {
        $override = $this->override($key);

        if ($override === null) {
            $line = __($key, $replace);

            return is_array($line) ? implode("\n\n", $line) : (string) $line;
        }

        return $this->replace($override, $replace);
    }

    /** @return list<string> */
    public function paragraphs(string $key): array
    {
        $override = $this->override($key);

        if ($override === null) {
            return array_values((array) __($key));
        }

        return $this->splitParagraphs($override);
    }

    /** Il testo del file lingua, come lo vede chi scrive nel pannello. */
    public function original(string $key, string $locale): string
    {
        $line = trans($key, [], $locale);

        return is_array($line) ? implode("\n\n", $line) : (string) $line;
    }

    /**
     * Segnaposto (:name, :region) del testo originale che il testo nuovo non
     * contiene: toglierli romperebbe la frase a video (resterebbe il valore
     * mancante o, peggio, un ":region" letterale).
     *
     * @return list<string>
     */
    public function missingPlaceholders(string $key, string $locale, string $value): array
    {
        $required = $this->placeholders($this->original($key, $locale));

        return array_values(array_diff($required, $this->placeholders($value)));
    }

    /**
     * Salva i testi di una sezione. Un campo vuoto toglie il testo della
     * cliente per quella lingua (torna l'originale); una riga senza più
     * lingue viene cancellata.
     *
     * @param  array<string, array<string, string|null>>  $values  lingua => [chiave => testo]
     */
    public function save(string $section, array $values, ?User $editor = null): void
    {
        $blocks = $this->section($section)['blocks'] ?? throw new InvalidArgumentException("Sezione sconosciuta: {$section}");

        foreach (array_keys($blocks) as $key) {
            $block = ContentBlock::firstOrNew(['key' => $key]);

            foreach (self::LOCALES as $locale) {
                if (! array_key_exists($key, $values[$locale] ?? [])) {
                    continue;
                }

                $text = $this->normalize((string) $values[$locale][$key]);

                if ($text === '') {
                    $block->forgetTranslation('value', $locale);
                } else {
                    $block->setTranslation('value', $locale, $text);
                }
            }

            $this->persist($block, $editor);
        }
    }

    /** "Ripristina il testo originale": toglie il testo della cliente per una lingua. */
    public function restore(string $key, string $locale, ?User $editor = null): void
    {
        $block = ContentBlock::where('key', $key)->first();

        if ($block === null) {
            return;
        }

        $block->forgetTranslation('value', $locale);

        $this->persist($block, $editor);
    }

    /**
     * Stato di una sezione per l'elenco delle pagine: per lingua, se c'è
     * almeno un testo riscritto; e quando è stata toccata l'ultima volta.
     *
     * @return array{locales: array<string, bool>, updated_at: CarbonInterface|null}
     */
    public function sectionStatus(string $section): array
    {
        $keys = array_keys($this->section($section)['blocks'] ?? []);

        $rows = ContentBlock::query()->whereIn('key', $keys)->get(['key', 'value', 'updated_at']);

        $locales = [];

        foreach (self::LOCALES as $locale) {
            $locales[$locale] = $rows->contains(
                fn (ContentBlock $block): bool => trim((string) $block->getTranslation('value', $locale, false)) !== '',
            );
        }

        return ['locales' => $locales, 'updated_at' => $rows->max('updated_at')];
    }

    /** @return list<string> */
    public function splitParagraphs(string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R\s*\R/u', trim($text)) ?: []),
            fn (string $paragraph): bool => $paragraph !== '',
        ));
    }

    /** @return list<string> in minuscolo: ":Region" e ":region" sono lo stesso segnaposto */
    private function placeholders(string $text): array
    {
        preg_match_all('/:([A-Za-z][A-Za-z0-9_]*)/', $text, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1])));
    }

    /** Stessa sostituzione del translator di Laravel (:name, :Name, :NAME). */
    private function replace(string $line, array $replace): string
    {
        if ($replace === []) {
            return $line;
        }

        uksort($replace, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $pairs = [];

        foreach ($replace as $key => $value) {
            $value = (string) $value;
            $pairs[':'.$key] = $value;
            $pairs[':'.ucfirst($key)] = ucfirst($value);
            $pairs[':'.mb_strtoupper($key)] = mb_strtoupper($value);
        }

        return strtr($line, $pairs);
    }

    /** Righe vuote multiple ridotte a una, spazi in coda tolti. */
    private function normalize(string $text): string
    {
        $text = str_replace("\r\n", "\n", trim($text));

        return (string) preg_replace("/\n{3,}/", "\n\n", $text);
    }

    private function persist(ContentBlock $block, ?User $editor): void
    {
        $filled = array_filter(
            $block->getTranslations('value'),
            fn ($value): bool => trim((string) $value) !== '',
        );

        if ($filled === []) {
            if ($block->exists) {
                $block->delete();
            }

            return;
        }

        if ($block->isDirty()) {
            $block->updated_by = $editor?->id;
            $block->save();
        }
    }
}
