{{-- Dashboard B2B – 2 (XD "Dashboard B2B – 2", artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <div class="mx-auto w-full max-w-[910px]">

                {{-- Card benvenuto (XD: 910x233, r10, bordo #E9E9E9) con scia di zampe cyan --}}
                <div class="relative overflow-hidden rounded-[10px] border border-gray-150 bg-white px-6 py-8">
                    <h1 class="text-[25px] font-bold text-[#0D171A]">{{ __('partner.dashboard.welcome', ['name' => $partnerName]) }}</h1>
                    <p class="mt-3 max-w-[640px] text-[15px] leading-relaxed text-[#1E2E33]">{{ __('partner.dashboard.intro') }}</p>
                    <flux:button href="{{ route('partner.service.create') }}" class="!mt-8 !h-10 !rounded-full !border-0 !bg-[#232A2C] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">{{ __('partner.dashboard.cta') }}</flux:button>

                    {{-- Scia di zampe decorativa (XD: 5 zampe cyan in diagonale, angolo alto-destra) --}}
                    <div class="pointer-events-none absolute inset-0 hidden origin-bottom-right rotate-6 text-brand-cyan sm:block" aria-hidden="true">
                        <flux:icon.animal class="absolute right-[98px] bottom-[111px] h-6 w-6" />
                        <flux:icon.animal class="absolute right-[104px] bottom-[87px] h-6 w-6" />
                        <flux:icon.animal class="absolute right-[84px] bottom-[67px] h-6 w-6" />
                        <flux:icon.animal class="absolute right-[86px] bottom-[36px] h-6 w-6" />
                        <flux:icon.animal class="absolute right-[64px] bottom-[8px] h-6 w-6" />
                    </div>
                </div>

                {{-- Statistiche (XD: 3 card 294x105, r10, bordo #E9E9E9). Numeri reali del
                     partner; senza il badge di variazione % del mockup, che non ha una
                     fonte (nessuno storico da confrontare). --}}
                <div class="mt-3.5 grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                    @foreach ($stats as $stat)
                        <div class="rounded-[10px] border border-gray-150 bg-white px-6 py-5" wire:key="stat-{{ $loop->index }}">
                            <p class="text-[15px] text-[#959595]">{{ __($stat['label']) }}</p>
                            <p class="mt-2 text-[32px] leading-none font-semibold text-black">{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
