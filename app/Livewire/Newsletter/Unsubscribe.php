<?php

namespace App\Livewire\Newsletter;

use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Newsletter\SubscriptionService;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pagina di disiscrizione dal link in fondo alle mail. La rotta è firmata
 * (middleware `signed`): arrivarci è la prova di avere la mail in mano.
 *
 * Il GET mostra solo il pulsante: i filtri antispam e le anteprime dei link
 * aprono gli URL delle mail da soli, e un GET che disiscrive toglierebbe dalla
 * lista chi non l'ha mai chiesto. Disiscrive l'azione del pulsante.
 */
class Unsubscribe extends Component
{
    #[Locked]
    public int $subscriberId;

    #[Locked]
    public string $email = '';

    public bool $done = false;

    public function mount(NewsletterSubscriber $subscriber): void
    {
        $this->subscriberId = $subscriber->getKey();
        $this->email = $subscriber->email;
        $this->done = $subscriber->status === NewsletterSubscriber::STATUS_UNSUBSCRIBED;
    }

    public function unsubscribe(SubscriptionService $subscriptions): void
    {
        $subscriber = NewsletterSubscriber::find($this->subscriberId);

        if ($subscriber !== null) {
            $subscriptions->unsubscribe($subscriber);
        }

        $this->done = true;
    }

    public function render()
    {
        return view('livewire.newsletter.unsubscribe')
            ->title(__('newsletter.unsubscribe.page_title'));
    }
}
