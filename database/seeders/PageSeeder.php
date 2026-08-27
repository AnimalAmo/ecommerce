<?php

namespace Database\Seeders;

use App\Models\Page\Page;
use Illuminate\Database\Seeder;

/**
 * Carica le pagine redazionali dai file HTML versionati in seeders/content/.
 * Il testo sta in git — una revisione legale si legge come diff — e il DB è
 * solo la sorgente a runtime.
 *
 * ATTENZIONE: quando arriverà il CRUD di backoffice questo updateOrCreate va
 * cambiato in firstOrCreate, altrimenti un db:seed cancella le modifiche fatte
 * dal cliente.
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
            'last_updated_at' => '2026-08-26',
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

            Page::updateOrCreate(['slug' => $slug], [
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
