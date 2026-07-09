{{-- News – Dettaglio (XD: "News – dettaglio", artboard 1920x1772) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pt-8 pb-[110px]">
            {{-- Indietro (XD: simbolo "Indietro" a 142,142 — freccia + label 13px #959595) --}}
            <a href="{{ route('news') }}" class="inline-flex items-center gap-2 text-[13px] leading-6 text-[#959595] transition hover:text-ink">
                <flux:icon.arrow-back class="h-3 w-3 shrink-0" />
                {{ __('news.back') }}
            </a>

            {{-- Titolo articolo (XD: Nunito-Bold 36px nero, frame 709px) --}}
            <h1 class="mt-8 max-w-[709px] text-4xl font-bold text-black">{{ $article['title'] }}</h1>

            {{-- Corpo (frame 978x486, Nunito-Regular 16/24) + foto hero mascherata 620x451 r4 a destra --}}
            <div class="mt-4 flex items-start gap-10">
                <div class="min-w-0 max-w-[978px] flex-1 space-y-6 text-base font-normal leading-6 text-black">
                    @foreach ($body as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
                {{-- XD "Gruppo di maschere 12": clip 620x451 r=[4], foto scaleBehavior=fill → object-cover --}}
                <img src="{{ asset($hero) }}" alt="{{ $article['title'] }}" class="hidden h-[451px] w-[620px] shrink-0 rounded-[4px] object-cover xl:block">
            </div>

            {{-- Articoli correlati (XD: colonna x211..1711 → 1500px centrati; 4 card 354x482, gap 28) --}}
            <section class="mx-auto mt-[60px] w-full max-w-[1500px]">
                <h2 class="text-2xl font-medium text-black">{{ __('news.related') }}</h2>

                <div class="mt-5 grid grid-cols-4 gap-[28px]">
                    @foreach ($related as $item)
                        <article wire:key="related-{{ $item['slug'] }}" class="group relative flex h-[482px] flex-col rounded-[3px] border border-gray-150 bg-white p-[10px]">
                            <div class="overflow-hidden rounded-t-[3px]">
                                <img src="{{ asset('img/xd/'.$item['img'].'.jpg') }}" alt="{{ $item['title'] }}" class="h-[223px] w-full object-cover transition duration-500 group-hover:scale-105">
                            </div>
                            <div class="flex flex-1 flex-col px-2.5 pb-2">
                                {{-- Data: XD usa Roboto-Regular, font non caricato nel progetto → fallback sans di sistema (come /news) --}}
                                <p class="mt-[26px] flex items-center gap-2 font-[Roboto,sans-serif] text-sm text-[#959595]">
                                    <flux:icon.calendar class="h-[19px] w-[19px] shrink-0 text-[#959595]" />
                                    {{ $item['date'] }}
                                </p>
                                <h3 class="mt-4 max-w-[314px] text-base font-semibold leading-[21px] text-black">{{ $item['title'] }}</h3>
                                <p class="mt-2.5 line-clamp-4 max-w-[314px] text-sm font-normal leading-[23px] text-[#555555]">{{ $item['excerpt'] }}</p>
                                <a href="{{ route('news.detail', $item['slug']) }}" class="relative z-[2] mx-auto mt-auto pt-4 text-sm font-normal text-[#242C2C]">{{ __('news.read_more') }}</a>
                            </div>
                            {{-- Link overlay all'articolo correlato --}}
                            <a href="{{ route('news.detail', $item['slug']) }}" class="absolute inset-0 z-[1] rounded-[3px]" aria-label="{{ $item['title'] }}"></a>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </main>

    @include('partials.site-footer')
</div>
