<?php

namespace App\Livewire\Newsletter;

use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Newsletter\SubscriptionService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Form di iscrizione nel piede del sito. La frase del consenso mostrata
 * accanto al pulsante è la stessa che si salva come prova.
 *
 * Risposta identica per ogni indirizzo (nuovo, già iscritto, soppresso):
 * il form non deve dire chi è in lista.
 */
class SubscribeForm extends Component
{
    /** Richieste per IP nella finestra: oltre, si aspetta. */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 600;

    public string $email = '';

    public bool $done = false;

    public function subscribe(SubscriptionService $subscriptions): void
    {
        $key = 'newsletter-subscribe|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => __('newsletter.footer.throttled', [
                    'minutes' => (int) ceil(RateLimiter::availableIn($key) / 60),
                ]),
            ]);
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        $this->validate(['email' => ['required', 'string', 'email', 'max:191']]);

        $subscriptions->subscribe(
            email: $this->email,
            locale: app()->getLocale(),
            source: NewsletterSubscriber::SOURCE_FOOTER,
            consentText: __('newsletter.footer.consent'),
            ip: request()->ip(),
            // Niente Auth::user(): l'indirizzo scritto può non essere quello
            // dell'account. L'aggancio all'utente lo fa il service per email.
            userAgent: request()->userAgent(),
        );

        $this->reset('email');
        $this->done = true;
    }

    public function render()
    {
        return view('livewire.newsletter.subscribe-form');
    }
}
