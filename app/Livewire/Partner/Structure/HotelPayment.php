<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Forms\HotelPaymentForm;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HotelPayment extends Component
{
    use InteractsWithStructureDraft;

    public HotelPaymentForm $form;

    public function mount(): void
    {
        $this->form->setFrom($this->draft(), Auth::user()?->partnerProfile);
    }

    public function next(): void
    {
        $this->form->validate();
        $this->saveStep($this->form->toDraft(), 11);

        // Difetto W7 (audit del 28/09/2026): le coordinate restavano solo sulla
        // bozza e Profilo → "Metodo di pagamento" le chiedeva da capo. Vanno
        // anche sul profilo, come le salva PartnerProfilePayment::save(),
        // prima della chiusura: sono valide anche se la bozza non si pubblica.
        Auth::user()?->partnerProfile()->updateOrCreate([], $this->form->toProfile());

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
