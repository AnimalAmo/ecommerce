<?php

namespace App\Http\Controllers\Admin;

use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Admin\Newsletter\NewsletterAdmin;
use App\Support\Admin\CsvDownload;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Esporta" degli iscritti alla newsletter, con gli stessi filtri della
 * tabella: la lista con tutta la prova del consenso, il file da consegnare a
 * chi la chiede (un controllo, una richiesta di accesso ai dati).
 */
class NewsletterExportController
{
    public function __invoke(Request $request, NewsletterAdmin $admin): StreamedResponse
    {
        // Solo stringhe: un `?stato[]=` scritto a mano non deve diventare un 500.
        $filters = array_map(fn ($value): string => is_string($value) ? $value : '', $request->only(['search', 'status', 'source', 'locale']));
        $at = fn ($date): string => $date?->format('d/m/Y H:i:s') ?? '';

        $rows = $admin->subscribers($filters)->reorder()->lazyById(500)->map(fn (NewsletterSubscriber $subscriber): array => [
            $subscriber->email,
            __('admin-newsletter.status.'.$subscriber->status),
            __('admin-newsletter.locale.'.$subscriber->locale),
            __('admin-newsletter.source.'.$subscriber->source),
            $at($subscriber->requested_at),
            $subscriber->consent_text,
            $subscriber->consent_ip,
            $subscriber->consent_user_agent,
            $at($subscriber->confirmation_sent_at),
            $at($subscriber->confirmed_at),
            $subscriber->confirmation_ip,
            $subscriber->confirmation_user_agent,
            $at($subscriber->unsubscribed_at),
            $at($subscriber->suppressed_at),
        ]);

        return CsvDownload::make(
            'newsletter-iscritti-'.now()->format('Y-m-d').'.csv',
            array_map(fn (string $key): string => __('admin-newsletter.export.'.$key), [
                'email', 'status', 'locale', 'source', 'requested', 'consent_text', 'request_ip', 'request_ua',
                'confirmation_sent', 'confirmed', 'confirm_ip', 'confirm_ua', 'unsubscribed', 'suppressed',
            ]),
            $rows,
        );
    }
}
