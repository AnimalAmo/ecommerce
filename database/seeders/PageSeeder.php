<?php

namespace Database\Seeders;

use App\Models\Page\Page;
use Illuminate\Database\Seeder;

/**
 * Carica le pagine legali dai file HTML versionati in seeders/content/, solo
 * se la pagina non esiste ancora: da quando la cliente le modifica dal
 * pannello, il database è la fonte di verità e un db:seed non deve
 * riscriverle (firstOrCreate, mai updateOrCreate).
 *
 * Una revisione del testo fatta da noi passa quindi dal pannello o da una
 * migration dedicata, non dalla modifica di questi file: su un database già
 * seminato non avrebbe effetto.
 */
class PageSeeder extends Seeder
{
    /** @var array<string, array{title: array<string, string>, last_updated_at: string}> */
    private const PAGES = [
        Page::TERMS_CUSTOMERS => [
            'title' => ['it' => 'Termini e condizioni', 'en' => 'Terms and conditions'],
            'last_updated_at' => '2026-08-26',
        ],
        Page::TERMS_SUPPLIERS => [
            'title' => ['it' => 'Condizioni generali di adesione fornitore', 'en' => 'Supplier general terms of adhesion'],
            // Art. 8 riscritto dal cliente il 14/09/2026: la provvigione si
            // trattiene all'origine invece di essere fatturata a 30 giorni.
            'last_updated_at' => '2026-09-14',
        ],
        // Il documento non porta una data propria: questa è quella di consegna
        // dei testi da parte del cliente.
        Page::PRIVACY => [
            'title' => ['it' => 'Privacy Policy', 'en' => 'Privacy Policy'],
            'last_updated_at' => '2026-08-27',
        ],
    ];

    private const LOCALES = ['it', 'en'];

    public function run(): void
    {
        foreach (self::PAGES as $slug => $attributes) {
            $body = $this->bodies($slug);

            if ($body === []) {
                continue;
            }

            Page::firstOrCreate(['slug' => $slug], [
                'kind' => Page::KIND_LEGAL,
                'title' => $attributes['title'],
                'body' => $body,
                'last_updated_at' => $attributes['last_updated_at'],
            ]);
        }
    }

    /**
     * Le lingue senza file vengono saltate: il model ripiega sull'italiano,
     * così una traduzione che arriva dopo non blocca la pubblicazione.
     *
     * @return array<string, string>
     */
    private function bodies(string $slug): array
    {
        $bodies = [];

        foreach (self::LOCALES as $locale) {
            $path = database_path("seeders/content/{$slug}.{$locale}.html");

            if (is_file($path)) {
                $bodies[$locale] = trim(file_get_contents($path));
            }
        }

        return $bodies;
    }
}
