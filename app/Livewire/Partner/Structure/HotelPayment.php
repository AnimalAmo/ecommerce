<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Forms\HotelPaymentForm;
use Livewire\Component;

class HotelPayment extends Component
{
    use InteractsWithStructureDraft;

    public HotelPaymentForm $form;

    public function mount(): void
    {
        $this->form->setFromDraft($this->draft());
    }

    public function next(): void
    {
        $this->form->validate();
        $this->saveStep($this->form->toDraft(), 11);

        // Ultimo step: pubblica o mette in attesa di Stripe. Si resta qui solo
        // se la bozza non è pubblicabile, col toast che lo spiega.
        if ($this->completeDraft()) {
            $this->redirectRoute('partner.dashboard');
        }
    }

    public function skip(): void
    {
        // "Inserisci più tardi": completa senza i dati di pagamento. Nessun
        // saveStep: lo step finale (11) lo scrive DraftCompleter, anche quando
        // la bozza resta in attesa di Stripe (prima restava a 10).
        if ($this->completeDraft()) {
            $this->redirectRoute('partner.dashboard');
        }
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-payment')
            ->title(__('partner.hotel_payment.title'));
    }
}
