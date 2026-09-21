{{-- Modale di conferma di ConfirmsCatalogActions (sospendi / riattiva / elimina). --}}
<flux:modal name="catalog-confirm" class="w-full max-w-[460px]" wire:close="$set('confirming', null)">
    @if ($confirming)
        <div class="flex flex-col">
            <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ $confirming['title'] }}</flux:heading>
            <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ $confirming['body'] }}</p>

            @if ($confirming['blocked'])
                <div class="mt-4 flex gap-2.5 rounded-lg border border-[#FFE0B2] bg-[#FFFBF4] px-[15px] py-[13px]" role="alert">
                    <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0 text-[#B45A0B]" />
                    <p class="m-0 text-[13px] leading-normal text-gray-600">{{ $confirming['blocked'] }}</p>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                <flux:modal.close>
                    <x-admin.button>{{ __('admin.actions.cancel') }}</x-admin.button>
                </flux:modal.close>
                <x-admin.button
                    :tone="$confirming['action'] === 'delete' ? 'danger' : 'primary'"
                    wire:click="confirmAction"
                >{{ $confirming['confirm'] }}</x-admin.button>
            </div>
        </div>
    @endif
</flux:modal>
