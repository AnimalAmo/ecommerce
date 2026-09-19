<?php

namespace App\Livewire\Admin\Content;

use App\Services\Content\ContentBlockService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Testi di una pagina del sito (home, chi siamo, …): un campo per ogni testo
 * descrittivo del registro config/admin-content.php, per lingua. Un campo
 * vuoto significa "resta il testo originale".
 *
 * I campi sono indicizzati per posizione, non per chiave: le chiavi hanno i
 * punti (home.hero_title) e wire:model li leggerebbe come annidamento.
 */
class SitePageEdit extends Component
{
    #[Locked]
    public string $section = '';

    /** @var list<string> */
    #[Locked]
    public array $keys = [];

    public string $locale = 'it';

    /** @var array<string, array<int, string>> lingua => [indice => testo] */
    public array $values = [];

    public function mount(string $section, ContentBlockService $blocks): void
    {
        $definition = $blocks->section($section);

        abort_if($definition === null, 404);

        $this->section = $section;
        $this->keys = array_keys($definition['blocks']);

        foreach (ContentBlockService::LOCALES as $locale) {
            foreach ($this->keys as $index => $key) {
                $this->values[$locale][$index] = (string) $blocks->override($key, $locale);
            }
        }
    }

    /** Le schede impostano `locale` con $set: fuori elenco si torna all'italiano. */
    public function updatedLocale(): void
    {
        if (! in_array($this->locale, ContentBlockService::LOCALES, true)) {
            $this->locale = 'it';
        }
    }

    public function save(ContentBlockService $blocks): void
    {
        $this->resetErrorBag();

        $this->validate([
            'values.*.*' => ['nullable', 'string', 'max:5000'],
        ], [], ['values.*.*' => 'testo']);

        $errors = [];

        foreach (ContentBlockService::LOCALES as $locale) {
            foreach ($this->keys as $index => $key) {
                $value = trim((string) ($this->values[$locale][$index] ?? ''));

                if ($value === '') {
                    continue;
                }

                $missing = $blocks->missingPlaceholders($key, $locale, $value);

                if ($missing !== []) {
                    $errors["values.{$locale}.{$index}"] = 'Il testo deve contenere '
                        .implode(', ', array_map(fn (string $token): string => ':'.$token, $missing))
                        .': al suo posto il sito scrive il valore giusto.';
                }
            }
        }

        if ($errors !== []) {
            foreach ($errors as $field => $message) {
                $this->addError($field, $message);
            }

            // Porta la cliente sulla lingua dove c'è il primo errore.
            $this->locale = explode('.', array_key_first($errors))[1];

            return;
        }

        $payload = [];

        foreach (ContentBlockService::LOCALES as $locale) {
            foreach ($this->keys as $index => $key) {
                $payload[$locale][$key] = (string) ($this->values[$locale][$index] ?? '');
            }
        }

        $blocks->save($this->section, $payload, Auth::user());

        Flux::toast(text: 'Testi salvati e pubblicati.', variant: 'success');
    }

    /** "Ripristina il testo originale" per un campo, nella lingua aperta. */
    public function restore(int $index, ContentBlockService $blocks): void
    {
        $key = $this->keys[$index] ?? null;

        if ($key === null) {
            return;
        }

        $blocks->restore($key, $this->locale, Auth::user());

        $this->values[$this->locale][$index] = '';
        $this->resetErrorBag("values.{$this->locale}.{$index}");

        Flux::toast(text: 'Torna il testo originale.', variant: 'success');
    }

    public function render(ContentBlockService $blocks)
    {
        $definition = $blocks->section($this->section);

        $fields = [];

        foreach ($this->keys as $index => $key) {
            $fields[] = [
                'index' => $index,
                'key' => $key,
                'label' => $definition['blocks'][$key]['label'],
                'type' => $definition['blocks'][$key]['type'],
                'original' => $blocks->original($key, $this->locale),
                'saved' => $blocks->override($key, $this->locale) !== null,
            ];
        }

        $status = $blocks->sectionStatus($this->section);

        return view('livewire.admin.content.site-page-edit', [
            'definition' => $definition,
            'fields' => $fields,
            'status' => $status,
            'isOriginal' => ! $status['locales'][$this->locale],
            'publicUrl' => route($definition['route']),
        ])
            ->layout('layouts::admin')
            ->title($definition['label']);
    }
}
