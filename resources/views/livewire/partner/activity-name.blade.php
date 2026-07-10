{{-- Dashboard B2B – tipologia attività/eventi - nome (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x329, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 2 di 10" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.activity_name.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.activity_name.step') }}</span>
                </div>

                <h2 class="mt-4 text-lg font-medium text-[#0D171A]">{{ __('partner.activity_name.section') }}</h2>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.activity_name.helper') }}</p>

                <form wire:submit="next" class="mt-6">
                    {{-- Campo unico a tutta larghezza (XD: 752x40, r3, bordo #C8C8C8) --}}
                    <flux:field>
                        <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.activity_name.field_label') }}</flux:label>
                        <flux:input wire:model="name" class="[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8]" />
                    </flux:field>

                    {{-- Azioni: Indietro (a tipologia attività/eventi) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.activity.type') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.activity_name.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.activity_name.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
