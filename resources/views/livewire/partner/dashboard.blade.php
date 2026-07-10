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
                    <flux:button href="#" class="!mt-8 !h-10 !rounded-full !border-0 !bg-[#232A2C] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">{{ __('partner.dashboard.cta') }}</flux:button>

                    {{-- Scia di zampe decorativa (XD: 5 zampe cyan in diagonale, angolo alto-destra) --}}
                    <div class="pointer-events-none absolute inset-0 hidden origin-bottom-right rotate-6 text-brand-cyan sm:block" aria-hidden="true">
                        <flux:icon.animal class="absolute right-[98px] bottom-[111px] h-6 w-6" />
                        <flux:icon.animal class="absolute right-[104px] bottom-[87px] h-6 w-6" />
                        <flux:icon.animal class="absolute right-[84px] bottom-[67px] h-6 w-6" />
                        <flux:icon.animal class="absolute right-[86px] bottom-[36px] h-6 w-6" />
                        <flux:icon.animal class="absolute right-[64px] bottom-[8px] h-6 w-6" />
                    </div>
                </div>

                {{-- Statistiche (XD: 3 card 294x105, r10, bordo #E9E9E9) --}}
                <div class="mt-3.5 grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                    @foreach ($stats as $stat)
                        <div class="rounded-[10px] border border-gray-150 bg-white px-6 py-5" wire:key="stat-{{ $loop->index }}">
                            <p class="text-[15px] text-[#959595]">{{ __($stat['label']) }}</p>
                            <div class="mt-2 flex items-end justify-between gap-2">
                                <span class="text-[32px] font-semibold leading-none text-black">{{ $stat['value'] }}</span>
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-[15px] text-[#2B2B2B] {{ $stat['positive'] ? 'bg-[#E5FDE7]' : 'bg-[#FDE8E3]' }}">
                                    <flux:icon.arrow-up-right class="h-3.5 w-3.5 {{ $stat['positive'] ? 'text-[#40E24E]' : 'rotate-90 text-[#F85933]' }}" />
                                    {{ $stat['delta'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
