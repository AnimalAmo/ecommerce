<?php

namespace App\Livewire\Newsletter;

use App\Services\Newsletter\SubscriptionService;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pagina del link di conferma (double opt-in).
 *
 * Il GET mostra solo il pulsante, come la disiscrizione: i filtri antispam
 * aziendali e le anteprime dei link aprono gli URL delle mail da soli, e un
 * GET che conferma iscriverebbe chi non ha mai cliccato — con l'IP del
 * filtro come prova. La conferma è il clic sul pulsante, e la prova ne
 * registra IP e user agent.
 */
class ConfirmSubscription extends Component
{
    #[Locked]
    public string $token = '';

    #[Locked]
    public bool $valid = false;

    #[Locked]
    public string $email = '';

    public bool $confirmed = false;

    public function mount(string $token, SubscriptionService $subscriptions): void
    {
        $subscriber = $subscriptions->findByConfirmationToken($token);

        $this->token = $token;
        $this->valid = $subscriber !== null;
        $this->email = (string) $subscriber?->email;
        $this->confirmed = (bool) $subscriber?->isConfirmed();
    }

    public function confirm(SubscriptionService $subscriptions): void
    {
        $subscriber = $subscriptions->confirm($this->token, request()->ip(), request()->userAgent());

        $this->valid = $subscriber !== null;
        $this->confirmed = $subscriber !== null;
    }

    public function render()
    {
        return view('livewire.newsletter.confirm-subscription')
            ->title(__('newsletter.confirm.page_title'));
    }
}
