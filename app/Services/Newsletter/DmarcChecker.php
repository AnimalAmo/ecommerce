<?php

namespace App\Services\Newsletter;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * C'è un record DMARC sul dominio del mittente della newsletter? Senza, Gmail
 * e Yahoo mettono gli invii di massa nello spam
 * (docs/newsletter-mailgun-istruzioni-cliente.md §A2.1).
 *
 * Il DMARC di un sottodominio (news.animalamo.it) ricade su quello del
 * dominio organizzativo (animalamo.it): si guarda prima il dominio esatto, poi
 * gli ultimi due livelli.
 *
 * Risultato in cache un'ora. È una query DNS: l'editor la chiede dopo il
 * caricamento della pagina (wire:init), mai durante il render, e nei test si
 * sostituisce la classe nel container (lookup() è il solo punto di rete).
 */
class DmarcChecker
{
    public function domain(): string
    {
        $from = config('newsletter.from.address') ?: config('mail.from.address');

        return Str::lower(Str::after((string) $from, '@'));
    }

    public function hasRecord(?string $domain = null): bool
    {
        $domain ??= $this->domain();

        if ($domain === '') {
            return false;
        }

        return Cache::remember('newsletter:dmarc:'.$domain, now()->addHour(), function () use ($domain): bool {
            $candidates = array_unique([$domain, implode('.', array_slice(explode('.', $domain), -2))]);

            foreach ($candidates as $candidate) {
                foreach ($this->lookup('_dmarc.'.$candidate) as $record) {
                    if (str_starts_with(Str::lower(trim($record)), 'v=dmarc1')) {
                        return true;
                    }
                }
            }

            return false;
        });
    }

    /**
     * Record TXT di un nome. Un errore DNS vale "nessun record".
     *
     * @return array<int, string>
     */
    protected function lookup(string $name): array
    {
        $records = @dns_get_record($name, DNS_TXT);

        if (! is_array($records)) {
            return [];
        }

        return array_map(
            fn (array $record): string => (string) ($record['txt'] ?? implode('', $record['entries'] ?? [])),
            $records,
        );
    }
}
