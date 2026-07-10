<?php

namespace App\Livewire\Partner\Profile;

use App\Livewire\Forms\PartnerProfileForm;
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
        $user->partnerProfile()->updateOrCreate([], $this->form->toProfile());

        Flux::toast(text: __('partner.profile.saved'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.partner.profile.info')
            ->title(__('partner.profile.info_title'));
    }
}
