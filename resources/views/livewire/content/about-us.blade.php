{{-- Chi siamo (XD: "Chi siamo", artboard 1920x2214; mobile: app "Chi siamo", 375x2633) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero full-bleed (XD: foto 1920x524, titolo Nunito Bold 45 bianco centrato; app: 375x261, titolo Bold 18) --}}
        <section class="relative h-[261px] w-full overflow-hidden lg:h-[524px]">
            <img src="{{ asset('img/xd/about-hero.jpg') }}" alt="{{ __('about.hero_alt') }}" class="absolute inset-0 h-full w-full object-cover">
        </section>

        {{-- Colonna contenuti XD: x211..1709 → 1498px centrati dentro il container $px --}}
        <div class="{{ $px }} pt-[68px] pb-[120px] max-lg:pt-5 max-lg:pb-10">
            {{-- App: le due card fotografiche sono intercalate ai blocchi di testo (foto → testo → foto → testo),
                 quindi su mobile la riga di card diventa `contents` e l'ordine viene ricomposto con `order`. --}}
            <div class="mx-auto flex w-full max-w-[1498px] flex-col">

                {{-- 2. Chi siamo (XD: titolo Nunito Bold 36 a y660, paragrafi 18 regular a y725; app: 18 / 15-22 #2B2B2B) --}}
                <h1 class="text-lg font-bold text-[#0D171A] max-lg:order-1 lg:text-4xl lg:text-black">{{ __('about.heading') }}</h1>
                <div class="mt-4 space-y-[22px] text-[15px] leading-[22px] text-[#2B2B2B] max-lg:order-1 lg:mt-[29px] lg:space-y-6 lg:text-lg lg:leading-7 lg:text-black">
                    @foreach (__('about.body') as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>

                {{-- 3. Card fotografiche (XD y973..1338: 738x365 + 736x365, gap 24, r7, overlay nero 25%;
                       app: 343x365 impilate, overlay 30% / 25%, testo 16 medium su 2 righe, bottone 39px) --}}
                <div class="mt-12 flex flex-col gap-6 max-lg:contents lg:flex-row">
                    {{-- Card sinistra: Animal Holiday --}}
                    <div class="relative flex min-h-[365px] flex-1 flex-col items-center justify-center overflow-hidden rounded-[7px] px-3 max-lg:order-2 max-lg:mt-3.5 lg:px-6">
                        <img src="{{ asset('img/xd/about-holiday.jpg') }}" alt="{{ __('about.holiday_alt') }}" class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-black/30 lg:bg-black/25" aria-hidden="true"></div>
                        <flux:button href="{{ route('holiday') }}" class="relative !h-[39px] !rounded-full !border-0 !bg-[#0D171A] !px-[33px] !text-sm !font-bold !text-white !shadow-none hover:!bg-[#232A2C] lg:!h-10 lg:!text-[15px]">{{ __('about.holiday_cta') }}</flux:button>
                    </div>
                    {{-- Card destra: Eventi e attività --}}
                    <div class="relative flex min-h-[365px] flex-1 flex-col items-center justify-center overflow-hidden rounded-[7px] px-3 max-lg:order-4 max-lg:mt-[22px] lg:px-6">
                        <img src="{{ asset('img/xd/about-events.jpg') }}" alt="{{ __('about.events_alt') }}" class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-black/25" aria-hidden="true"></div>
                        <flux:button href="{{ route('eventi') }}" class="relative !h-[39px] !rounded-full !border-0 !bg-[#0D171A] !px-[33px] !text-sm !font-bold !text-white !shadow-none hover:!bg-[#232A2C] lg:!h-10 lg:!text-[15px]">{{ __('about.events_cta') }}</flux:button>
                    </div>
                </div>

                {{-- 4. Blocchi testo (XD: Nunito Bold 28 a y1385 / y1559, paragrafi 18 regular; app: 18 / 15-22) --}}
                <h2 class="mt-[47px] text-lg font-bold text-[#0D171A] max-lg:order-3 max-lg:mt-7 lg:text-[28px] lg:text-black">{{ __('about.block1_heading') }}</h2>
                <div class="mt-[18px] space-y-[22px] text-[15px] leading-[22px] text-[#2B2B2B] max-lg:order-3 max-lg:mt-1.5 lg:space-y-6 lg:text-lg lg:leading-7 lg:text-black">
                    @foreach (__('about.block1_body') as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>

                <h2 class="mt-[46px] text-lg font-bold text-[#0D171A] max-lg:order-5 max-lg:mt-7 lg:text-[28px] lg:text-black">{{ __('about.block2_heading') }}</h2>
                <div class="mt-[18px] space-y-[22px] text-[15px] leading-[22px] text-[#2B2B2B] max-lg:order-5 max-lg:mt-1.5 lg:space-y-6 lg:text-lg lg:leading-7 lg:text-black">
                    @foreach (__('about.block2_body') as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>

                {{-- 5. CTA gialla (XD: "Button giallo" 223x39 r20 #EDFF00, bordo #E9FF7D, testo Bold 14 #0D171A; app: centrata) --}}
                <div class="mt-[50px] max-lg:order-6 max-lg:mt-[22px] max-lg:flex max-lg:justify-center">
                    <flux:button href="{{ route('holiday') }}" class="!h-[39px] !rounded-full !border !border-[#E9FF7D] !bg-brand-yellow !px-8 !text-sm !font-bold !text-ink !shadow-none hover:!bg-[#0D171A] hover:!border-[#0D171A] hover:!text-white">{{ __('about.explore_cta') }}</flux:button>
                </div>

            </div>
        </div>
    </main>

    @include('partials.site-footer')
</div>
