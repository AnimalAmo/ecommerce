<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class HotelPayment extends Component
{
    public string $accountHolder = '';

    public string $iban = '';

    public string $sdi = '';

    public string $bic = '';

    public function next(): void
    {
        $this->validate([
            'accountHolder' => ['required', 'string', 'max:128'],
            'iban' => ['required', 'string', 'max:34'],
            'sdi' => ['required', 'string', 'max:7'],
            'bic' => ['required', 'string', 'max:11'],
        ]);

        // Ultimo step: onboarding struttura completato.
        // TODO: persist the structure once the partner backend exists.
        $this->redirectRoute('partner.dashboard');
    }

    public function skip(): void
    {
        // "Inserisci più tardi": completa senza i dati di pagamento.
        $this->redirectRoute('partner.dashboard');
    }

    public function render()
    {
        return view('livewire.partner.hotel-payment')
            ->title(__('partner.hotel_payment.title'));
    }
}
