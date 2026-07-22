{{-- News (XD: "News", artboard 1920x1806; mobile: app "News", 375x2341) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- Colonna contenuti XD: x211..1709 → 1498px centrati dentro il container $px --}}
        <div class="{{ $px }} pt-10 pb-[120px] max-lg:pt-5 max-lg:pb-8">
            <div class="mx-auto w-full max-w-[1498px]">
                <h1 class="text-lg font-bold text-[#0D171A] lg:text-4xl lg:text-black">{{ __('news.title') }}</h1>

                {{-- Griglia news (XD: simbolo "Box News" 480x482, 3 colonne × 2 righe; ombra 0 1 5 #0000001A, senza bordo).
                     App: colonna singola di card 343x309 con bordo #E9E9E9 e senza ombra, foto 327x136. --}}
                <div class="mt-6 grid grid-cols-1 gap-3 lg:mt-10 lg:grid-cols-3 lg:gap-x-[29px] lg:gap-y-5">
                    @foreach ($articles as $article)
                        <article wire:key="news-{{ $loop->index }}" class="group relative flex flex-col rounded-[3px] border border-gray-150 bg-white p-2 lg:border-0 lg:p-[10px] lg:pt-2 lg:shadow-[0_1px_5px_#0000001A]">
                            <div class="overflow-hidden rounded-t-[3px]">
                                <img src="{{ asset('img/xd/'.$article['img'].'.jpg') }}" alt="{{ $article['title'] }}" class="h-[136px] w-full object-cover transition duration-500 group-hover:scale-105 lg:h-[237px]">
                            </div>
                            <div class="flex flex-1 flex-col lg:px-4 lg:pb-1.5">
                                {{-- Data: XD usa Roboto-Regular, font non caricato nel progetto → fallback sans di sistema (come home) --}}
                                <p class="mt-2.5 flex items-center gap-1.5 font-[Roboto,sans-serif] text-xs text-[#959595] lg:mt-3.5 lg:gap-2 lg:text-sm">
                                    <flux:icon.calendar class="h-4 w-4 shrink-0 text-[#959595] lg:h-[19px] lg:w-[19px]" />
                                    {{ $article['date'] }}
                                </p>
                                <h3 class="mt-1 max-w-[369px] text-base font-semibold leading-[22px] text-[#0D171A] lg:mt-4 lg:text-[20px] lg:leading-[25px] lg:text-black">{{ $article['title'] }}</h3>
                                @if (filled($article['excerpt']))
                                    <p class="mt-3 line-clamp-2 max-w-[428px] text-[15px] leading-[22px] font-normal text-[#2B2B2B] lg:mt-[18px] lg:line-clamp-4 lg:text-sm lg:leading-[23px] lg:text-[#555555]">{{ $article['excerpt'] }}</p>
                                @endif
                                {{-- App: link in corsivo allineato a sinistra; desktop resta centrato e in tondo --}}
                                <a href="{{ route('news.detail', $article['slug']) }}" class="relative z-[2] mt-2.5 mr-auto text-sm font-normal italic text-[#959595] lg:mx-auto lg:mt-auto lg:pt-5 lg:not-italic lg:text-[#242C2C]">{{ __('news.read_more') }}</a>
                            </div>
                            {{-- Link overlay all'articolo --}}
                            <a href="{{ route('news.detail', $article['slug']) }}" class="absolute inset-0 z-[1] rounded-[3px]" aria-label="{{ $article['title'] }}"></a>
                        </article>
                    @endforeach
                </div>

                {{-- Bottone "Carica altro" (XD: simbolo "Button vedi tutto" 145x40 r20 #0D171A, label override; app: 140x39, label 14) --}}
                <div class="mt-6 flex justify-center lg:mt-10">
                    {{-- TODO: azione Carica altro --}}
                    <flux:button class="!h-[39px] !rounded-full !border-0 !bg-[#0D171A] !px-[33px] !text-sm !font-bold !text-white !shadow-none hover:!bg-[#232A2C] lg:!h-10 lg:!px-8 lg:!text-[15px]">{{ __('news.load_more') }}</flux:button>
                </div>
            </div>
        </div>
    </main>

    @include('partials.site-footer')
</div>
