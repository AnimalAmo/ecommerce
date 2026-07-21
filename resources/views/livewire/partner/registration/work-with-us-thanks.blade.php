{{-- Lavora con noi – thank you (XD: "Lavora con noi – thankyou page"; app: "Diventa partner - click 'chiudi'") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    {{-- Banda gradiente (XD: 296deg #FF3EA526 → #68CDEB33); resta identica anche su mobile --}}
    <main class="flex-1 bg-[linear-gradient(296deg,#FF3EA526_0%,#68CDEB33_100%)]">
        <div class="{{ $px }} flex justify-center pt-[160px] pb-32 max-lg:pt-10 max-lg:pb-10">
            {{-- Card (XD desktop: 865x358, bianco op 0.5, bordo #E9E9E9, r 3;
                 XD app: pannello #EBF9FD bordo #6CD1EF, titolo 18 bold ciano, testo 15 #555555) --}}
            <div class="relative w-full max-w-[865px] rounded-[3px] border border-gray-150 bg-white/50 px-8 pb-[70px] pt-[74px] text-center max-lg:rounded-[10px] max-lg:border-brand-cyan max-lg:bg-[#EBF9FD] max-lg:px-6 max-lg:py-10">
                <flux:heading level="1" class="!text-4xl !font-bold !text-brand-cyan max-lg:!text-lg">{{ __('partner.thanks_heading') }}</flux:heading>

                <p class="mt-8 text-lg text-black max-lg:mt-4 max-lg:text-[15px] max-lg:text-[#555555]">{{ __('partner.thanks_line_1') }}<br>{{ __('partner.thanks_line_2') }}</p>

                <div class="mt-10 max-lg:mt-8">
                    <flux:button href="{{ route('home') }}" class="!rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white max-lg:!h-[39px] max-lg:!w-full">{{ __('partner.back_home') }}</flux:button>
                </div>

                {{-- Aeroplanino di carta (XD "Tracciato 654", rotazione 135° già applicata al path) --}}
                <svg viewBox="-41.72 -19.83 28.27 28.27" aria-hidden="true" class="absolute right-[119px] top-[276px] hidden h-[28px] w-[28px] text-brand-cyan md:block">
                    <path fill="currentColor" d="M -41.72 8.06 C -41.72 8.10 -41.71 8.14 -41.70 8.17 C -41.70 8.18 -41.69 8.20 -41.69 8.21 C -41.67 8.25 -41.64 8.29 -41.61 8.33 C -41.57 8.36 -41.53 8.39 -41.49 8.41 C -41.47 8.41 -41.46 8.42 -41.45 8.42 C -41.42 8.43 -41.38 8.44 -41.34 8.44 C -41.33 8.44 -41.32 8.44 -41.31 8.44 C -41.27 8.44 -41.22 8.44 -41.18 8.42 L -13.72 -1.59 C -13.56 -1.64 -13.45 -1.80 -13.45 -1.97 C -13.45 -2.14 -13.56 -2.29 -13.72 -2.35 L -26.35 -6.93 L -30.93 -19.56 C -30.99 -19.72 -31.14 -19.83 -31.31 -19.83 C -31.42 -19.83 -31.53 -19.79 -31.60 -19.71 C -31.64 -19.67 -31.67 -19.62 -31.69 -19.56 L -41.70 7.90 C -41.72 7.94 -41.72 7.99 -41.72 8.03 C -41.72 8.04 -41.72 8.05 -41.72 8.06 Z M -15.04 -1.97 L -39.74 7.03 L -26.56 -6.14 L -15.04 -1.97 Z M -27.13 -6.72 L -40.31 6.46 L -31.31 -18.24 L -27.13 -6.72 Z" />
                </svg>
            </div>
        </div>
    </main>

    {{-- Su mobile il footer lascia il posto alla tabbar (XD app) --}}
    <div class="max-lg:hidden">
        @include('partials.footer-minimal')
    </div>
    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>
</div>
