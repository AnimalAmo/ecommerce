<?php

namespace App\Livewire\Partner\MyServices;

use App\Models\Structure\StructureDraft;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    /** Etichetta tag tipologia (Holiday / Eventi / Smartbox) per il badge. */
    public function tag(): string
    {
        return __('partner.services.tag_'.$this->draft->family());
    }

    /**
     * Righe dell'accordion (XD "Dettaglio ... – dettagli"): label + contenuto.
     * Le chiavi-opzione salvate su DB sono risolte in label localizzate; i testi
     * liberi del partner restano come inseriti.
     */
    public function rows(): array
    {
        $draft = $this->draft;
        $none = __('partner.services.not_provided');
        $join = fn (array $values): string => implode(', ', array_filter($values)) ?: $none;

        $location = collect([
            $draft->address,
            trim($draft->city.' '.($draft->province ? "({$draft->province})" : '')),
            $draft->zip,
            $draft->license,
        ])->filter()->implode(' · ');

        $cancellation = match (true) {
            $draft->cancellation_when === '1' => __('partner.services.cancellation_day'),
            filled($draft->cancellation_when) => __('partner.services.cancellation_days', ['days' => $draft->cancellation_when]),
            default => $none,
        };

        $payment = collect([
            $draft->account_holder,
            $draft->iban,
            $draft->bic,
            $draft->sdi ? 'SDI '.$draft->sdi : null,
        ])->filter()->implode(' · ');

        return [
            ['label' => __('partner.services.section_type'), 'text' => ServiceOptionLabels::label('type', $draft->type) ?? $none],
            ['label' => __('partner.services.section_name'), 'text' => $draft->name ?: $none],
            ['label' => __('partner.services.section_location'), 'text' => $location ?: $none],
            ['label' => __('partner.services.section_description'), 'text' => $draft->description ?: $none],
            [
                'label' => __('partner.services.section_rooms'),
                'rooms' => $draft->rooms ?: [],
                'checkin' => [$draft->checkin_from, $draft->checkin_to],
                'checkout' => [$draft->checkout_from, $draft->checkout_to],
                'text' => filled($draft->rooms) ? null : $none,
            ],
            ['label' => __('partner.services.section_cancellation'), 'text' => $cancellation],
            ['label' => __('partner.services.section_services'), 'text' => $join([
                ...ServiceOptionLabels::labels('services', $draft->services),
                ...ServiceOptionLabels::labels('additional', $draft->additional_services),
                $draft->additional_other,
                ...ServiceOptionLabels::labels('rules', $draft->rules),
            ])],
            ['label' => __('partner.services.section_extra'), 'text' => $join([
                ...ServiceOptionLabels::labels('animal_services', $draft->animal_services),
                $draft->animal_services_other,
            ])],
            [
                'label' => __('partner.services.section_photos'),
                'photos' => array_map(fn ($p) => Storage::disk('public')->url($p), $draft->photos ?? []),
                'text' => filled($draft->photos) ? null : $none,
            ],
            ['label' => __('partner.services.section_payment'), 'text' => $payment ?: $none],
        ];
    }

    public function render()
    {
        return view('livewire.partner.my-services.detail', [
            'tag' => $this->tag(),
            'rows' => $this->rows(),
        ])->title(__('partner.services.detail_title'));
    }
}
