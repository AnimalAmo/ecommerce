{{-- Iscrizione B2B - step 2 (XD "Iscrizione B2B – step 2", artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $services = [
        'struttura' => ['partner.register2.struttura_title', 'partner.register2.struttura_subtitle'],
        'attivita' => ['partner.register2.attivita_title', 'partner.register2.attivita_subtitle'],
        'servizi' => ['partner.register2.servizi_title', 'partner.register2.servizi_subtitle'],
    ];
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x429, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata card: titolo + "Step 2 di 2" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.register.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.register2.step') }}</span>
                </div>

                <h2 class="mt-4 text-lg font-medium text-[#0D171A]">{{ __('partner.register2.section') }}</h2>

                {{-- Righe servizio selezionabili (multi-scelta): cerchio XD r8 — selezionato #68CDEB con check --}}
                <div class="mt-6">
                    @foreach ($services as $key => [$titleKey, $subtitleKey])
                        @php $selected = in_array($key, $this->services, true); @endphp
                        <flux:button variant="ghost" wire:click="toggle('{{ $key }}')" wire:key="svc-{{ $key }}" class="!h-auto !w-full !justify-start !rounded-none !p-0 !py-[18px] !text-left hover:!bg-transparent [&>span]:flex [&>span]:w-full [&>span]:items-start [&>span]:gap-3">
                            <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ $selected ? 'bg-brand-cyan' : 'border border-[#C8C8C8] bg-white' }}">
                                @if ($selected)
                                    <flux:icon.check class="h-[10px] w-[10px] text-black" />
                                @endif
                            </span>
                            <span class="flex flex-col gap-1">
                                <span class="text-[15px] font-semibold text-[#1E2E33]">{{ __($titleKey) }}</span>
                                <span class="text-sm font-normal text-[#627277]">{{ __($subtitleKey) }}</span>
                            </span>
                        </flux:button>
                        <div class="h-px bg-[#E2EAEB]" aria-hidden="true"></div>
                    @endforeach
                </div>

                @error('services')
                    <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror

                {{-- Azioni: Indietro (link a step 1) + Crea un account (pill scuro) --}}
                <div class="mt-8 flex items-center justify-end gap-6">
                    <flux:button href="{{ route('partner.register') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.register.back') }}</flux:button>
                    <flux:button wire:click="createAccount" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.register2.submit') }}</flux:button>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
