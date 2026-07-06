{{-- News (XD: "News", artboard 1920x1806) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- Colonna contenuti XD: x211..1709 → 1498px centrati dentro il container $px --}}
        <div class="{{ $px }} pt-10 pb-[120px]">
            <div class="mx-auto w-full max-w-[1498px]">
                <h1 class="text-4xl font-bold text-black">News</h1>
                <p class="mt-4 max-w-[1295px] text-lg leading-6 text-black">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>

                {{-- Griglia news (XD: simbolo "Box News" 480x482, 3 colonne × 2 righe; ombra 0 1 5 #0000001A, senza bordo) --}}
                <div class="mt-10 grid grid-cols-3 gap-x-[29px] gap-y-5">
                    @foreach ($articles as $article)
                        <article wire:key="news-{{ $loop->index }}" class="group relative flex flex-col rounded-[3px] bg-white p-[10px] pt-2 shadow-[0_1px_5px_#0000001A]">
                            <div class="overflow-hidden rounded-t-[3px]">
                                <img src="{{ asset('img/xd/'.$article['img'].'.jpg') }}" alt="{{ $article['title'] }}" class="h-[237px] w-full object-cover transition duration-500 group-hover:scale-105">
                            </div>
                            <div class="flex flex-1 flex-col px-4 pb-1.5">
                                {{-- Data: XD usa Roboto-Regular, font non caricato nel progetto → fallback sans di sistema (come home) --}}
                                <p class="mt-3.5 flex items-center gap-2 font-[Roboto,sans-serif] text-sm text-[#959595]">
                                    <flux:icon.calendar class="h-[19px] w-[19px] shrink-0 text-[#959595]" />
                                    {{ $article['date'] }}
                                </p>
                                <h3 class="mt-4 max-w-[369px] text-[20px] font-semibold leading-[25px] text-black">{{ $article['title'] }}</h3>
                                <p class="mt-[18px] line-clamp-4 max-w-[428px] text-sm leading-[23px] font-normal text-[#555555]">{{ $article['excerpt'] }}</p>
                                <a href="{{ route('news.detail', $article['slug']) }}" class="relative z-[2] mx-auto mt-auto pt-5 text-sm font-normal text-[#242C2C]">Continua a leggere…</a>
                            </div>
                            {{-- Link overlay all'articolo --}}
                            <a href="{{ route('news.detail', $article['slug']) }}" class="absolute inset-0 z-[1] rounded-[3px]" aria-label="{{ $article['title'] }}"></a>
                        </article>
                    @endforeach
                </div>

                {{-- Bottone "Carica altro" (XD: simbolo "Button vedi tutto" 145x40 r20 #0D171A, label override) --}}
                <div class="mt-10 flex justify-center">
                    {{-- TODO: azione Carica altro --}}
                    <flux:button class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">Carica altro</flux:button>
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
