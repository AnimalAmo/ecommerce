{{-- Chi siamo (XD: "Chi siamo", artboard 1920x2214) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero full-bleed (XD: foto 1920x524, titolo Nunito Bold 45 bianco centrato) --}}
        <section class="relative h-[524px] w-full overflow-hidden">
            <img src="{{ asset('img/xd/about-hero.jpg') }}" alt="Chi siamo" class="absolute inset-0 h-full w-full object-cover">
            <div class="relative flex h-full items-center justify-center px-4">
                <p class="text-center text-[45px] font-bold leading-tight text-white">Lorem ipsum dolor sit sed est</p>
            </div>
        </section>

        {{-- Colonna contenuti XD: x211..1709 → 1498px centrati dentro il container $px --}}
        <div class="{{ $px }} pt-[68px] pb-[120px]">
            <div class="mx-auto w-full max-w-[1498px]">

                {{-- 2. Chi siamo (XD: titolo Nunito Bold 36 a y660, paragrafi 18 regular a y725) --}}
                <h1 class="text-4xl font-bold text-black">Chi siamo</h1>
                <div class="mt-[29px] space-y-6 text-lg text-black">
                    <p>{{ $lorem }}</p>
                    <p>{{ $lorem }}</p>
                </div>

                {{-- 3. Card fotografiche (XD y973..1338: 738x365 + 736x365, gap 24, r7, overlay nero 25%) --}}
                <div class="mt-12 flex flex-col gap-6 lg:flex-row">
                    {{-- Card sinistra: Animal Holiday --}}
                    <div class="relative flex min-h-[365px] flex-1 flex-col items-center justify-center overflow-hidden rounded-[7px] px-6">
                        <img src="{{ asset('img/xd/about-holiday.jpg') }}" alt="Vacanze pet friendly" class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-black/25" aria-hidden="true"></div>
                        <p class="relative max-w-[576px] text-center text-lg font-medium text-white">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam est.</p>
                        <flux:button href="{{ route('holiday') }}" class="relative !mt-[34px] !h-10 !rounded-full !border-0 !bg-[#0D171A] !px-[33px] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">Scegli la tua prossima vacanza</flux:button>
                    </div>
                    {{-- Card destra: Eventi e attività --}}
                    <div class="relative flex min-h-[365px] flex-1 flex-col items-center justify-center overflow-hidden rounded-[7px] px-6">
                        <img src="{{ asset('img/xd/about-events.jpg') }}" alt="Eventi e attività pet friendly" class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-black/25" aria-hidden="true"></div>
                        <p class="relative max-w-[576px] text-center text-lg font-medium text-white">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam est.</p>
                        <flux:button href="{{ route('eventi') }}" class="relative !mt-[34px] !h-10 !rounded-full !border-0 !bg-[#0D171A] !px-[33px] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">Scopri eventi e attività</flux:button>
                    </div>
                </div>

                {{-- 4. Blocchi testo (XD: Nunito Bold 28 a y1385 / y1559, paragrafi 18 regular) --}}
                <h2 class="mt-[47px] text-[28px] font-bold text-black">Vivi il tuo viaggio al meglio</h2>
                <p class="mt-[18px] text-lg text-black">{{ $lorem }}</p>

                <h2 class="mt-[46px] text-[28px] font-bold text-black">Trova le migliori avventure</h2>
                <p class="mt-[18px] text-lg text-black">{{ $lorem }}</p>

                {{-- 5. CTA gialla (XD: "Button giallo" 223x39 r20 #EDFF00, bordo #E9FF7D, testo Bold 14 #0D171A) --}}
                <div class="mt-[50px]">
                    <flux:button href="{{ route('holiday') }}" class="!h-[39px] !rounded-full !border !border-[#E9FF7D] !bg-brand-yellow !px-8 !text-sm !font-bold !text-ink !shadow-none hover:!bg-[#0D171A] hover:!border-[#0D171A] hover:!text-white">Esplora tutte le proposte</flux:button>
                </div>

            </div>
        </div>
    </main>

    @include('partials.site-footer')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />
</div>
