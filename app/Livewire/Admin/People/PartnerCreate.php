<?php

namespace App\Livewire\Admin\People;

use App\Exceptions\PartnerAccountException;
use App\Exceptions\PaymentModeException;
use App\Livewire\Forms\Admin\PartnerCreateForm;
use App\Models\Region\Province;
use App\Services\Admin\People\PartnerAccountService;
use Flux\Flux;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Nuovo partner": l'admin crea l'account di chi ha chiamato o scritto, o
 * promuove un cliente. Il componente passa i dati e mostra l'esito. Le regole
 * stanno in PartnerAccountService.
 */
class PartnerCreate extends Component
{
    public PartnerCreateForm $form;

    /** Account che ha già l'email (un partner): la scheda da aprire, accanto all'errore. */
    #[Locked]
    public ?int $existingUserId = null;

    public function save(PartnerAccountService $accounts): void
    {
        $this->existingUserId = null;

        $data = $this->form->validate();

        try {
            $created = $accounts->create($data);
        } catch (PartnerAccountException $e) {
            $this->existingUserId = $e->user?->id;
            $this->addError('form.email', $e->getMessage());

            return;
        } catch (PaymentModeException) {
            // Cliente con un vecchio profilo offline che chiede l'online senza Stripe.
            $this->addError('form.paymentMode', __('partner.payment_mode.errors.stripe_required', [], 'it'));

            return;
        }

        // L'account esiste comunque: se qualcosa dopo il commit è fallito, l'admin
        // lo sa e lo sistema dalla scheda (Cambia / Invia di nuovo il link).
        $warnings = array_keys(array_filter([
            'payment_mode_failed' => ! $created->paymentModeSaved,
            'welcome_failed' => ! $created->welcomeSent,
        ]));

        if ($warnings === []) {
            Flux::toast(text: __('admin-people.partner_create.'.($created->promoted ? 'promoted' : 'created'), [], 'it'), variant: 'success');
        } else {
            Flux::toast(
                text: collect($warnings)->map(fn (string $key): string => __('admin-people.partner_create.'.$key, [], 'it'))->implode(' '),
                variant: 'warning',
            );
        }

        $this->redirectRoute('admin.users.show', $created->user, navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.people.partner-create', [
            'provinces' => Province::orderBy('name')->get(),
        ])
            ->layout('layouts::admin')
            ->title(__('admin-people.partner_create.title'));
    }
}
