<?php

namespace App\Livewire\Admin\People;

use App\Enums\OrderPaymentMode;
use App\Exceptions\PartnerAccountException;
use App\Exceptions\PaymentModeException;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Admin\People\AnonymizeUser;
use App\Services\Admin\People\PartnerAccountService;
use App\Services\Admin\People\UserAccountStatus;
use App\Services\Admin\People\UserDirectory;
use App\Services\Partner\PartnerPaymentModeService;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

/** Scheda di un iscritto: dati, ruolo, stato account, newsletter, ordini e prenotazioni, animali, candidature. */
class UserShow extends Component
{
    public User $user;

    public ?int $anonymizingId = null;

    /** Modale "Cambia" della modalità di pagamento: online | on_site. */
    public string $paymentMode = 'online';

    public string $paymentUrl = '';

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

        Flux::toast(text: __('admin-people.users.'.($this->user->is_active ? 'reactivated' : 'deactivated')), variant: 'success');
    }

    /**
     * Il profilo su cui agisce la card partner, con la stessa condizione con
     * cui render() decide di disegnarla: il ruolo, non la sola riga di
     * partner_profiles. Un cliente con un profilo rimasto da prima (il caso
     * che PartnerAccountService::create() gestisce) non mostra né il bottone
     * né la modale, e non deve rispondere nemmeno ai metodi.
     */
    private function partnerProfile(): ?PartnerProfile
    {
        return $this->user->hasRole('partner') ? $this->user->partnerProfile : null;
    }

    /** Apre la modale con la modalità e il link di oggi. */
    public function editPaymentMode(): void
    {
        $profile = $this->partnerProfile();

        if ($profile === null) {
            return;
        }

        $this->paymentMode = $profile->paymentMode()->value;
        $this->paymentUrl = (string) $profile->payment_url;
        $this->resetErrorBag();

        Flux::modal('payment-mode')->show();
    }

    /**
     * Stessa regola del profilo partner, dallo stesso service: offline →
     * online solo da pagabile, offline sempre. Il rifiuto è un toast, in
     * italiano forzato: il messaggio dell'eccezione nasce nella lingua della
     * richiesta.
     */
    public function setPaymentMode(PartnerPaymentModeService $modes): void
    {
        $profile = $this->partnerProfile();

        if ($profile === null) {
            return;
        }

        $this->validate([
            'paymentMode' => ['required', Rule::enum(OrderPaymentMode::class)],
            'paymentUrl' => PartnerPaymentModeService::PAYMENT_URL_RULES,
        ]);

        try {
            // Una ValidationException di set() cade sotto `paymentUrl`, la stessa chiave del campo.
            $modes->set($profile, $this->paymentMode === OrderPaymentMode::Online->value, $this->paymentUrl);
        } catch (PaymentModeException) {
            Flux::toast(text: __('partner.payment_mode.errors.stripe_required', [], 'it'), variant: 'danger');

            return;
        }

        Flux::modal('payment-mode')->close();
        Flux::toast(text: __('admin-people.users.payment_mode_saved'), variant: 'success');
    }

    /** Ruolo e stato li verifica il service: qui solo l'esito a schermo. */
    public function resendWelcome(PartnerAccountService $accounts): void
    {
        try {
            $accounts->resendWelcome($this->user);
        } catch (PartnerAccountException $e) {
            Flux::toast(text: $e->getMessage(), variant: 'danger');

            return;
        }

        Flux::toast(text: __('admin-people.users.welcome_sent', ['email' => $this->user->email]), variant: 'success');
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'paymentMode' => __('admin-people.partner_create.fields.paymentMode', [], 'it'),
            'paymentUrl' => __('admin-people.partner_create.fields.paymentUrl', [], 'it'),
        ];
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
        Flux::toast(text: __('admin-people.anonymize.done'), variant: 'success');
    }

    public function render(UserDirectory $directory, AnonymizeUser $anonymizer)
    {
        $user = $this->user;

        $isPartner = $user->hasRole('partner');

        return view('livewire.admin.people.user-show', [
            'isPartner' => $isPartner,
            'partner' => $isPartner ? $directory->partnerSummary($user) : null,
            'newsletterState' => $directory->newsletterState($user),
            'orders' => $directory->orders($user),
            'pets' => $user->pets()->get(),
            'applications' => $user->partnerApplications()->latest('id')->get(),
            'anonymizing' => $this->anonymizingId !== null ? $user : null,
            'anonymizeBlock' => $this->anonymizingId !== null ? $anonymizer->blockReason($user) : null,
        ])
            ->layout('layouts::admin')
            ->title($user->name);
    }
}
