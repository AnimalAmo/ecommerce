{{-- News – Dettaglio (XD: "News – dettaglio", artboard 1920x1772; mobile: app "Dettaglio articolo", 375x1739) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pt-8 pb-[110px] max-lg:pt-4 max-lg:pb-8">
            {{-- Indietro (XD: simbolo "Indietro" a 142,142 — freccia + label 13px #959595; identico nell'app) --}}
            <a href="{{ route('news') }}" class="inline-flex items-center gap-2 text-[13px] leading-6 text-[#959595] transition hover:text-ink">
                <flux:icon.arrow-back class="h-3 w-3 shrink-0" />
                {{ __('news.back') }}
            </a>

            {{-- Foto hero mobile (XD app: 343x136 mascherata r3, sopra il titolo) --}}
            <img src="{{ asset($hero) }}" alt="{{ $article['title'] }}" class="mt-4 h-[136px] w-full rounded-[3px] object-cover lg:hidden">

            {{-- Titolo articolo (XD: Nunito-Bold 36px nero, frame 709px; app: 18px #0D171A) --}}
            <h1 class="mt-6 max-w-[709px] text-lg font-bold text-[#0D171A] lg:mt-8 lg:text-4xl lg:text-black">{{ $article['title'] }}</h1>

            {{-- Corpo (frame 978x486, Nunito-Regular 16/24; app: 15/22 #2B2B2B) + foto hero mascherata 620x451 r4 a destra --}}
            <div class="mt-2 flex items-start gap-10 lg:mt-4">
                <div class="min-w-0 max-w-[978px] flex-1 space-y-[22px] text-[15px] font-normal leading-[22px] text-[#2B2B2B] lg:space-y-6 lg:text-base lg:leading-6 lg:text-black">
                    @foreach ($body as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
                {{-- XD "Gruppo di maschere 12": clip 620x451 r=[4], foto scaleBehavior=fill → object-cover --}}
                <img src="{{ asset($hero) }}" alt="{{ $article['title'] }}" class="hidden h-[451px] w-[620px] shrink-0 rounded-[4px] object-cover xl:block">
            </div>

            {{-- Articoli correlati (XD: colonna x211..1711 → 1500px centrati; 4 card 354x482, gap 28) --}}
            <section class="mx-auto mt-10 w-full max-w-[1500px] lg:mt-[60px]">
                <h2 class="text-lg font-bold text-[#0D171A] lg:text-2xl lg:font-medium lg:text-black">{{ __('news.related') }}</h2>

                {{-- Carosello mobile (XD app "Scorri gruppo 7": card 280x200 r3 bordo #E9E9E9, padding 8,
                       foto 264x184 r3 con sfumatura nera in basso e titolo bianco 15px sopra). --}}
                <div class="-mx-4 mt-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-1 lg:hidden">
                    @foreach ($related as $item)
                        <a href="{{ route('news.detail', $item['slug']) }}" wire:key="related-mobile-{{ $item['slug'] }}" class="block h-[200px] w-[280px] shrink-0 snap-start rounded-[3px] border border-gray-150 bg-white p-2">
                            <div class="relative h-full w-full overflow-hidden rounded-[3px]">
                                <img src="{{ asset('img/xd/'.$item['img'].'.jpg') }}" alt="{{ $item['title'] }}" class="h-full w-full object-cover">
                                {{-- Sfumatura XD "Rettangolo 32": 141px dal basso, #000 80% → trasparente --}}
                                <div class="absolute inset-x-0 bottom-0 h-[141px] bg-gradient-to-t from-black/80 via-[#121212]/55 to-transparent" aria-hidden="true"></div>
                                {{-- XD ancora il titolo a 115px dal bordo alto della foto: vale sia a una riga sia a due --}}
                                <h3 class="absolute left-2 top-[115px] w-[218px] text-[15px] font-bold leading-5 text-white">{{ $item['title'] }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-5 hidden grid-cols-4 gap-[28px] lg:grid">
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
