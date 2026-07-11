<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class ActivityCost extends Component
{
    use InteractsWithStructureDraft;

    /** Opzione di costo: pagamento | gratuito. */
    public string $costType = '';

    /** Costo a persona (solo se "A pagamento"). */
    public string $pricePerPerson = '';

    public function mount(): void
    {
        $draft = $this->draft();
        $this->costType = $draft->price_type ?? '';
        $this->pricePerPerson = $draft->price_per_person ?? '';
    }

    public function next(): void
    {
        $rules = ['costType' => ['required', 'in:pagamento,gratuito']];
        if ($this->costType === 'pagamento') {
            // decimal:0,2 + max: il publisher converte in cents (unsignedInteger).
            $rules['pricePerPerson'] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'];
        }

        $this->validate($rules, [
            'costType.required' => __('partner.activity_cost.error_required'),
            'costType.in' => __('partner.activity_cost.error_required'),
        ]);

        $this->saveStep([
            'price_type' => $this->costType,
            'price_per_person' => $this->costType === 'pagamento' ? $this->pricePerPerson : null,
        ], 8);

        $this->redirectRoute('partner.activity.photos');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-cost')
            ->title(__('partner.activity_cost.title'));
    }
}
