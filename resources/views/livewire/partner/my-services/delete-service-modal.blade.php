{{-- Popup elimina servizio (XD "Pop-up elimina servizio") --}}
<div>
    <flux:modal name="delete-service" class="w-full !max-w-[520px] !rounded-[10px] bg-white !p-8">
        <h2 class="text-xl font-bold text-[#0D171A]">{{ __('partner.services.delete') }}</h2>
        <p class="mt-4 text-[15px] font-medium text-[#627277]">{{ __('partner.services.delete_confirm') }}</p>

        {{-- Anteprima servizio: immagine 103x64 + nome/indirizzo --}}
        <div class="mt-4 flex items-center gap-4">
            <div class="h-[64px] w-[103px] shrink-0 overflow-hidden bg-gray-100">
                @if ($cover)
                    <img src="{{ $cover }}" alt="{{ $name }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center bg-[linear-gradient(296deg,#FF3EA51A_0%,#68CDEB1A_100%)] text-brand-cyan">
                        <flux:icon.animal class="h-6 w-6" />
                    </div>
                @endif
            </div>
            <div>
                <p class="text-[15px] font-semibold text-[#1E2E33]">{{ $name }}</p>
                <p class="mt-1 flex items-center gap-1 text-sm font-medium text-[#959595]">
                    <flux:icon.pin class="h-4 w-4 shrink-0" />
                    {{ $location }}
                </p>
            </div>
        </div>

        <div class="mt-8 flex items-center justify-end gap-4">
            <flux:modal.close>
                <flux:button variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.services.delete_cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" class="!h-10 !rounded-full !border-0 !bg-[#F85933] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#EA451E]">{{ __('partner.services.delete_submit') }}</flux:button>
        </div>
    </flux:modal>
</div>
