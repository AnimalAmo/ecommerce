<?php

namespace App\Livewire\Admin\People;

use App\Exceptions\PartnerAccountException;
use App\Exceptions\PaymentModeException;
use App\Livewire\Forms\Admin\PartnerCreateForm;
use App\Models\Region\Province;
use App\Services\Admin\People\PartnerAccountService;
use Flux\Flux;
use Livewire\Attributes\Computed;
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

    /**
     * Anagrafica ferma (ProvinceSeeder): letta una volta e tenuta in cache,
     * o la tabella intera tornerebbe a ogni round trip del form. Array di
     * scalari e non model: con `cache.serializable_classes` a false una
     * Collection di Eloquent non si rileggerebbe dalla cache, e il giro
     * sarebbe inutile.
     *
     * @return list<array{name: string, short: string}>
     */
    #[Computed(persist: true)]
    public function provinces(): array
    {
        return Province::query()
            ->orderBy('name')
            ->get(['id', 'name', 'short_name'])
            ->map(fn (Province $province): array => [
                'name' => (string) $province->name,
                'short' => (string) $province->short_name,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.admin.people.partner-create')
            ->layout('layouts::admin')
            ->title(__('admin-people.partner_create.title'));
    }
}
