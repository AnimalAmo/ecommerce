{{-- "Nuova scheda": si sceglie il partner e la famiglia, il resto si compila
     nella pagina di creazione. I due select hanno `label` come prop, così
     Flux ci mette dentro il proprio flux:error: un campo composto a mano
     rifiuterebbe il submit senza mostrare niente. --}}
<flux:modal name="catalog-create" class="w-full max-w-[460px]">
    <div class="flex flex-col">
        <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-catalog.create.entry.modal_title') }}</flux:heading>
        <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ __('admin-catalog.create.entry.modal_body') }}</p>

        @if ($creatablePartners->isEmpty())
            <x-admin.notice tone="warning" class="mt-4">{{ __('admin-catalog.create.entry.no_partners') }}</x-admin.notice>
        @else
            <div class="mt-5 flex flex-col gap-4">
                <flux:select
                    wire:model="newPartner"
                    variant="listbox"
                    searchable
                    :label="__('admin-catalog.create.entry.modal_partner')"
                    :placeholder="__('admin-catalog.create.partner_placeholder')"
                >
                    @foreach ($creatablePartners as $id => $label)
                        <flux:select.option value="{{ $id }}" wire:key="create-partner-{{ $id }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="newFamily" :label="__('admin-catalog.create.entry.modal_family')">
                    @foreach ($creatableFamilies as $family)
                        <flux:select.option value="{{ $family }}" wire:key="create-family-{{ $family }}">{{ __('admin-catalog.create.entry.family.'.$family) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif

        <div class="mt-6 flex justify-end gap-2.5">
            <flux:modal.close>
                <x-admin.button>{{ __('admin.actions.cancel') }}</x-admin.button>
            </flux:modal.close>
            @if ($creatablePartners->isNotEmpty())
                <x-admin.button tone="primary" wire:click="startCreate">{{ __('admin-catalog.create.entry.modal_submit') }}</x-admin.button>
            @endif
        </div>
    </div>
</flux:modal>
