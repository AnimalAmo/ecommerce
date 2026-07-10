<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class HotelPayment extends Component
{
    use InteractsWithStructureDraft;

    public string $accountHolder = '';

    public string $iban = '';

    public string $sdi = '';

    public string $bic = '';

    public function mount(): void
    {
        $draft = $this->draft();
        $this->accountHolder = $draft->account_holder ?? '';
        $this->iban = $draft->iban ?? '';
        $this->sdi = $draft->sdi ?? '';
        $this->bic = $draft->bic ?? '';
    }

    public function next(): void
    {
        $this->validate([
            'accountHolder' => ['required', 'string', 'max:128'],
            'iban' => ['required', 'string', 'max:34'],
            'sdi' => ['required', 'string', 'max:7'],
            'bic' => ['required', 'string', 'max:11'],
        ]);

        $this->saveStep([
            'account_holder' => $this->accountHolder,
            'iban' => $this->iban,
            'sdi' => $this->sdi,
            'bic' => $this->bic,
        ], 11);

        // Ultimo step: onboarding struttura completato (stato -> completed).
        $this->completeDraft();
        $this->redirectRoute('partner.dashboard');
    }

    public function skip(): void
    {
        // "Inserisci più tardi": completa senza i dati di pagamento.
        $this->completeDraft();
        $this->redirectRoute('partner.dashboard');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-payment')
            ->title(__('partner.hotel_payment.title'));
    }
}
