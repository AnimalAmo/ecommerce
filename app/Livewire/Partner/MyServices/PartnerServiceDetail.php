<?php

namespace App\Livewire\Partner\MyServices;

use App\Models\Structure\StructureDraft;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PartnerServiceDetail extends Component
{
    public StructureDraft $draft;

    public function mount(StructureDraft $draft): void
    {
        // Solo i propri servizi completati sono visibili.
        abort_unless(
            $draft->user_id === Auth::id() && $draft->status === StructureDraft::STATUS_COMPLETED,
            403,
        );

        $this->draft = $draft;
    }

    public function render()
    {
        return view('livewire.partner.my-services.detail')
            ->title(__('partner.services.detail_title'));
    }
}
