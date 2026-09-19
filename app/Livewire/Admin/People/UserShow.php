<?php

namespace App\Livewire\Admin\People;

use App\Models\Order\Order;
use App\Models\User;
use App\Services\Admin\People\AnonymizeUser;
use App\Services\Admin\People\UserAccountStatus;
use App\Services\Admin\People\UserDirectory;
use Flux\Flux;
use Livewire\Component;
use RuntimeException;

/** Scheda di un iscritto: dati, stato account, newsletter, ordini, animali, candidature. */
class UserShow extends Component
{
    public User $user;

    public ?int $anonymizingId = null;

    public function mount(User $user): void
    {
        // I superadmin non sono iscritti: la loro scheda non esiste qui.
        abort_if($user->hasRole('superadmin'), 404);

        $this->user = $user;
    }

    public function toggleActive(UserAccountStatus $status): void
    {
        try {
            $status->setActive($this->user, ! $this->user->is_active);
        } catch (RuntimeException $e) {
            Flux::toast(text: $e->getMessage(), variant: 'danger');

            return;
        }

        Flux::toast(text: $this->user->is_active ? 'Account riattivato.' : 'Account disattivato.', variant: 'success');
    }

    public function askAnonymize(): void
    {
        $this->anonymizingId = $this->user->id;

        Flux::modal('anonymize-user')->show();
    }

    public function anonymize(AnonymizeUser $service): void
    {
        if (($reason = $service->blockReason($this->user)) !== null) {
            Flux::toast(text: $reason, variant: 'danger');

            return;
        }

        $service->handle($this->user);
        $this->user->refresh();

        $this->anonymizingId = null;
        Flux::modal('anonymize-user')->close();
        Flux::toast(text: 'Dati personali cancellati. Gli ordini restano in archivio.', variant: 'success');
    }

    public function render(UserDirectory $directory, AnonymizeUser $anonymizer)
    {
        $user = $this->user;

        return view('livewire.admin.people.user-show', [
            'isPartner' => $user->hasRole('partner'),
            'newsletterState' => $directory->newsletterState($user),
            'orders' => Order::query()->where('user_id', $user->id)->latest('id')->get(),
            'pets' => $user->pets()->get(),
            'applications' => $user->partnerApplications()->latest('id')->get(),
            'anonymizing' => $this->anonymizingId !== null ? $user : null,
            'anonymizeBlock' => $this->anonymizingId !== null ? $anonymizer->blockReason($user) : null,
        ])
            ->layout('layouts::admin')
            ->title($user->name);
    }
}
