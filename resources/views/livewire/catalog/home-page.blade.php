{{-- Container centrato stile matsuri: max width desktop, niente full-width stretched --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    {{-- ============ HEADER (partial condiviso) ============ --}}
    @include('partials.site-header')

    {{-- ============ HERO ============ --}}
    <section class="relative isolate overflow-hidden bg-brand-cyan-bg">
        <img src="{{ asset('img/home-hero.jpg') }}" alt="" aria-hidden="true" class="absolute inset-0 -z-10 h-full w-full object-cover">

        <div class="{{ $px }} flex min-h-[560px] max-h-[976px] flex-col justify-center py-24 lg:h-[calc(100svh-5rem)]">
            {{-- Box hero (stile XD: #152E36, radius 2px): titolo, testo, Dove/Quando --}}
            <div class="w-full max-w-2xl rounded-[2px] bg-[#152E36] px-4 py-6 shadow-[0px_3px_6px_#00000029]">
                <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-white">Viaggia con il tuo migliore amico.</h1>
                <p class="mt-4 text-sm leading-relaxed text-white/70">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.</p>

                {{-- Search bar stile XD: pill bianco (border #F4F4F4, radius 100px) con input + pulsante dentro --}}
                <form wire:submit="search" class="mt-8 flex w-full items-center gap-2 rounded-[100px] border border-[#F4F4F4] bg-white p-2">
                    <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                        <flux:label class="sr-only">Dove</flux:label>
                        <flux:icon.pin class="h-5 w-5 shrink-0 text-brand-cyan" />
                        <flux:input wire:model="where" type="text" placeholder="Dove" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:focus:!outline-none [&_input]:focus-visible:!outline-none [&_input]:placeholder:text-gray-400" />
                    </flux:field>
                    <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                    {{-- "Quando": trigger + popover Alpine col calendario range condiviso. Il popover NON si
                         chiude al click su un giorno (il range richiede 2 click); i wire:click del calendario
                         restano funzionanti dentro. Layout della pill invariato: stesso segmento flex-1. --}}
                    <div x-data="{ open: false }" class="relative flex flex-1 items-center">
                        <flux:button type="button" variant="ghost" x-on:click="open = ! open" class="!flex !h-auto !w-full !items-center !justify-start !gap-3 !rounded-none !bg-transparent !px-4 !py-2 !shadow-none hover:!bg-transparent [&>span]:!flex [&>span]:!min-w-0 [&>span]:!items-center [&>span]:!gap-3">
                            <flux:icon.calendar class="h-5 w-5 shrink-0 text-brand-cyan" />
                            <span class="truncate text-sm {{ $editCheckIn ? 'text-ink' : 'text-gray-400' }}">{{ $editCheckIn ? $editCheckIn.' – '.($editCheckOut ?? '…') : 'Quando' }}</span>
                        </flux:button>
                        <div x-show="open" x-on:click.outside="open = false" x-transition.opacity style="display: none" class="absolute left-0 top-full z-50 mt-3 w-[320px] rounded-[4px] border border-[#DEDEDE] bg-white p-[10px] text-left shadow-[0px_3px_6px_#00000029]">
                            @include('partials.booking.calendar', ['calendar' => $calendar, 'calendarLabel' => $calendarLabel])
                        </div>
                    </div>
                    <flux:button type="submit" square aria-label="Cerca" class="!h-auto !w-auto shrink-0 !rounded-full !bg-brand-cyan !p-3.5 !text-white hover:!bg-brand-cyan-soft">
                        <flux:icon.search class="h-5 w-5" />
                    </flux:button>
                </form>
            </div>

        </div>
    </section>

    {{-- ============ ANIMAL HOLIDAY ============ --}}
    <section id="holiday" class="{{ $px }} scroll-mt-20 py-20">
        <div class="mb-10 flex items-end justify-between">
            <div>
                <h2 class="text-4xl font-extrabold">Animal Holiday</h2>
                <p class="mt-3 max-w-xl text-lg text-gray-500">Hotel, B&amp;B e strutture dove il tuo animale è sempre il benvenuto.</p>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-6">
            @foreach ($regions as $region)
                <a href="{{ route('holiday.region', ['region' => $region->slug]) }}" wire:key="reg-{{ $region->id }}" class="group block rounded-[3px] border border-[#E9E9E9] bg-white p-[10px]">
                    <div class="relative overflow-hidden">
                        <img src="{{ asset('img/xd/'.$region->img.'.jpg') }}" alt="{{ __('catalog.region_title', ['region' => $region->name]) }}" class="h-80 w-full object-cover transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/10 to-transparent"></div>
                        <flux:badge class="absolute right-4 top-4 !rounded-[3px] !bg-brand-magenta !text-white">{{ __('catalog.structures_count', ['count' => $region->structures_count]) }}</flux:badge>
                        <h3 class="absolute bottom-4 left-4 pr-4 text-[20px] font-bold text-white">{{ __('catalog.region_title', ['region' => $region->name]) }}</h3>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-10 flex justify-center">
            <a href="{{ route('holiday') }}" class="rounded-full bg-[#0D171A] px-8 py-4 text-sm font-extrabold text-white transition hover:bg-[#232A2C]">Vedi tutto</a>
        </div>
    </section>

    {{-- ============ EVENTI ============ --}}
    <section id="eventi" class="scroll-mt-20">
        {{-- Banda immagine con gradiente XD: nero 60% a sx → trasparente a dx --}}
        <div class="relative isolate overflow-hidden">
            <img src="{{ asset('img/eventi-bg.jpg') }}" alt="" aria-hidden="true" class="absolute inset-0 -z-10 h-full w-full object-cover">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,#00000099_0%,#71717100_100%)]"></div>
            <div class="{{ $px }} flex min-h-[660px] flex-col justify-end pb-10 pt-24">
                <h2 class="text-4xl font-extrabold text-white">Eventi pet friendly</h2>
                <p class="mt-3 max-w-xl text-lg text-white/85">Esperienze, gite e attività da vivere insieme al tuo amico a quattro zampe.</p>
                <a href="#" class="mt-12 w-fit rounded-full bg-brand-cyan px-6 py-3 text-[15px] font-extrabold text-white transition hover:bg-[#68CDEB]">Scopri gli eventi</a>
            </div>
        </div>
        <div class="{{ $px }} pb-16 pt-6">
            <div class="grid grid-cols-5 gap-6">
                @foreach ($events as $event)
                    <div wire:key="ev-{{ $event->id }}" class="group flex h-full flex-col rounded-[3px] border border-[#E9E9E9] bg-white p-2">
                        <div class="relative overflow-hidden">
                            <img src="{{ asset('img/xd/'.$event->img.'.jpg') }}" alt="{{ $event->title }}" class="max-h-[227px] w-full object-cover transition duration-500 group-hover:scale-105">
                        </div>
                        <div class="flex flex-1 flex-col p-2 pt-3">
                            <p class="flex items-center gap-1.5 text-[13px] text-brand-purple-soft">
                                <flux:icon.time class="h-4 w-4 shrink-0" />
                                {{ \App\Support\Format::eventTimeSentence($event->starts_at) }}
                            </p>
                            <p class="mt-1 flex items-center gap-1.5 text-[13px] text-[#555555]">
                                <flux:icon.pin class="h-4 w-4 shrink-0 text-[#555555]" />
                                {{ $event->location }}
                            </p>
                            <h3 class="mt-[10px] text-[20px] font-semibold leading-snug text-black">{{ $event->title }}</h3>
                            {{-- Blocco pulsante+prezzo: sempre in fondo (mt-auto) e su un solo rigo,
                                 pulsante e prezzo affiancati; 'A partire da' più piccolo per starci. --}}
                            <div class="mt-auto flex items-center justify-between gap-2 pt-4">
                                <flux:button href="#" size="sm" class="shrink-0 !rounded-full !border-0 !bg-[#E9E9E9] !px-4 !text-sm !text-[#0D171A] !shadow-none hover:!bg-brand-yellow">
                                    <flux:icon.check-1 class="h-4 w-4" />
                                    Partecipa
                                </flux:button>
                                @if ($event->price_cents !== null)
                                    <p class="shrink-0 whitespace-nowrap text-right text-[13px] font-normal text-[#627277]">A partire da <span class="text-[15px] font-semibold text-[#0D171A]">€ {{ \App\Support\Format::amount($event->price_cents) }}</span></p>
                                @else
                                    <p class="shrink-0 whitespace-nowrap text-[15px] italic text-[#627277]">{{ __('format.free') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 flex justify-center">
                <a href="#" class="rounded-full bg-[#0D171A] px-8 py-4 text-sm font-extrabold text-white transition hover:bg-[#232A2C]">Vedi tutto</a>
            </div>
        </div>
    </section>

    {{-- ============ SMARTBOX ============ --}}
    <section id="smartbox" class="{{ $px }} scroll-mt-20 pb-20">
        {{-- Card group stile XD: immagine + card bianca attaccate, shadow 1px 1px 10px --}}
        <div class="mx-20 grid min-h-[660px] grid-cols-2 shadow-[1px_1px_10px_#0000001A]">
            <img src="{{ asset('img/smartbox.jpg') }}" alt="Smartbox" class="h-full min-h-[660px] w-full object-cover">
            <div class="flex flex-col items-end justify-center bg-white p-16 text-right">
                <h2 class="text-[36px] font-bold leading-tight text-black">Acquista una Smartbox</h2>
                <p class="mt-4 text-[18px] text-[#555555]">Cofanetti di soggiorni ed esperienze pet-friendly. Il regalo perfetto per chi ama viaggiare con il proprio animale.</p>
                <flux:button href="{{ route('smartbox') }}" class="mt-8 w-fit !rounded-full !border-0 !bg-brand-cyan !px-6 !py-3 !text-[15px] !font-extrabold !text-white !shadow-none hover:!bg-[#68CDEB]">Trova il regalo giusto</flux:button>
            </div>
        </div>
    </section>

    {{-- ============ NEWS ============ --}}
    <section id="news" class="scroll-mt-20 bg-brand-cyan-bg py-8">
        <div class="{{ $px }}">
            <div class="text-center">
                <h2 class="text-[36px] font-bold text-black">News</h2>
                <p class="mt-3 text-[18px] font-normal text-[#555555]">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.</p>
            </div>
            <div class="mt-10 grid grid-cols-3 gap-6">
                @foreach ($news as $article)
                    <div wire:key="news-{{ $loop->index }}" class="rounded-[3px] bg-white px-[10px] py-2">
                        <img src="{{ asset('img/xd/'.$article['img'].'.jpg') }}" alt="{{ $article['title'] }}" class="h-[237px] w-full object-cover">
                        <div class="p-2">
                            <p class="flex items-center gap-1.5 font-[Roboto,sans-serif] text-sm text-[#959595]">
                                <flux:icon.calendar class="h-4 w-4 shrink-0 text-[#959595]" />
                                {{ $article['date'] }}
                            </p>
                            <h3 class="my-4 text-[20px] font-semibold text-black">{{ $article['title'] }}</h3>
                            <p class="mb-4 text-sm font-normal text-[#555555]">{{ $article['excerpt'] }}</p>
                            <div class="flex justify-center">
                                {{-- Le card home non hanno slug proprio: si risolve per immagine su News::ARTICLES (la card fuori elenco rimanda a /news) --}}
                                @php $newsSlug = collect(\App\Livewire\Content\News::ARTICLES)->firstWhere('img', $article['img'])['slug'] ?? null; @endphp
                                <flux:button variant="ghost" :href="$newsSlug ? route('news.detail', $newsSlug) : route('news')" class="!text-sm !font-normal !text-[#242C2C] hover:!bg-transparent">Continua a leggere...</flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 flex justify-center">
                <a href="{{ route('news') }}" class="rounded-full bg-[#0D171A] px-8 py-4 text-sm font-extrabold text-white transition hover:bg-[#232A2C]">Vedi tutto</a>
            </div>
        </div>
    </section>

    {{-- ============ COMMUNITY ============ --}}
    <section id="community" class="scroll-mt-20">
        <div class="relative isolate overflow-hidden">
            <img src="{{ asset('img/footer-community.jpg') }}" alt="" aria-hidden="true" class="absolute inset-0 -z-10 h-full w-full object-cover">
            {{-- Gradiente XD: nero 60% a dx → trasparente a sx --}}
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(270deg,#00000099_0%,#71717100_100%)]"></div>
            <div class="{{ $px }} flex min-h-[660px] flex-col pb-[98px]">
                <div class="my-auto">
                    <h2 class="text-4xl font-extrabold text-white">Community</h2>
                    <p class="mt-3 max-w-md text-lg text-white/85">Confrontati con altri pet-lover: consigli, racconti di viaggio e domande prima di partire.</p>
                    <a href="#" class="mt-12 inline-block rounded-full bg-brand-cyan px-6 py-3 text-[15px] font-extrabold text-white transition hover:bg-[#68CDEB]">Scopri la community</a>
                </div>
                {{-- Box recensione XD: glass bianco su foto, blur 7px --}}
                <div class="max-w-xl self-end rounded-[4px] border border-gray-150 bg-white/10 p-4 backdrop-blur-[7px]">
                    <div class="flex items-center justify-between gap-6">
                        <p class="text-[13px] font-semibold text-brand-yellow">25/11/23</p>
                        <p class="text-[13px] font-semibold text-brand-yellow">6 Risposte</p>
                    </div>
                    <p class="mt-2 text-lg text-white mb-2">"Qualcuno ha consigli per un primo viaggio in treno con un cane di taglia media? Vorrei che fosse un'esperienza tranquilla per entrambi 🐾"</p>
                    <p class="text-lg italic text-white">- Sofia</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    @include('partials.site-footer')
</div>
