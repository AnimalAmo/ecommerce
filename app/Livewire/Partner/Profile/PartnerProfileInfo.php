<?php

namespace App\Livewire\Partner\Profile;

use App\Livewire\Forms\PartnerProfileForm;
use App\Models\Region\Province;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PartnerProfileInfo extends Component
{
    public PartnerProfileForm $form;

    public function mount(): void
    {
        $this->form->setFromUser(Auth::user());
    }

    public function save(): void
    {
        $this->form->validate();

        $user = Auth::user();
        $user->update($this->form->toUser());
        // La data del primo consenso ai recapiti pubblici: se la spunta resta
        // data, un nuovo salvataggio non la sposta.
        $user->partnerProfile()->updateOrCreate([], $this->form->toProfile(
            $user->partnerProfile?->public_contacts_consent_at,
        ));

        Flux::toast(text: __('partner.profile.saved'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.partner.profile.info', [
            'provinces' => Province::orderBy('name')->get(),
        ])->title(__('partner.profile.info_title'));
    }
}
