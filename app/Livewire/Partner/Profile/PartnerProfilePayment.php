<?php

namespace App\Livewire\Partner\Profile;

use App\Livewire\Forms\PartnerPaymentForm;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PartnerProfilePayment extends Component
{
    public PartnerPaymentForm $form;

    public function mount(): void
    {
        $this->form->setFromProfile(Auth::user()->partnerProfile);
    }

    public function save(): void
    {
        $this->form->validate();

        Auth::user()->partnerProfile()->updateOrCreate([], $this->form->toProfile());

        Flux::toast(text: __('partner.profile.saved'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.partner.profile.payment')
            ->title(__('partner.profile.payment_title'));
    }
}
