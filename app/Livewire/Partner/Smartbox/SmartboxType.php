<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SmartboxType extends Component
{
    use InteractsWithStructureDraft;

    /** Tipologia smartbox scelta: soggiorno | benessere | avventura. */
    public string $type = '';

    public function mount(): void
    {
        // La colonna `type` è condivisa; per lo smartbox vale soggiorno|benessere|avventura.
        $type = $this->draft()->type;
        $this->type = in_array($type, ['soggiorno', 'benessere', 'avventura'], true) ? $type : '';
    }

    public function next(): void
    {
        $this->validate(
            ['type' => ['required', 'string', 'in:soggiorno,benessere,avventura']],
            ['type.required' => __('partner.smartbox_type.error_required'), 'type.in' => __('partner.smartbox_type.error_required')],
        );

        $this->saveStep(['type' => $this->type], 1);
        $this->redirectRoute('partner.smartbox.name');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-type', [
            'backUrl' => $this->serviceChoiceBackUrl(),
            // Avvisa, non blocca (richiesta della cliente del 27/09/2026): una
            // smartbox già fatta deve restare modificabile, e chi è al primo
            // step deve sapere PRIMA di compilare dodici sezioni che senza
            // incasso online il cofanetto non andrà in vetrina.
            'paymentRequired' => Auth::user()?->partnerProfile?->canPublishFamily('smartbox') !== true,
        ])->title(__('partner.smartbox_type.title'));
    }
}
