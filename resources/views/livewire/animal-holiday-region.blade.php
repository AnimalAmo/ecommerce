{{-- Animal Holiday – Regione (XD: "Animal Holiday – Regione") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pt-10 pb-20">
            <h1 class="text-4xl font-bold text-black">Hotel e servizi in {{ $regionName }}</h1>
            <p class="mt-4 max-w-[1295px] text-lg leading-6 text-black">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>

            {{-- Barra ricerca (simbolo condiviso, pre-compilata con la regione) --}}
            <form wire:submit="search" class="mt-10 flex w-full items-center gap-2 rounded-[100px] border border-[#F4F4F4] bg-white p-2 shadow-[1px_1px_10px_#0000001A]">
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                    <flux:label class="sr-only">Dove</flux:label>
                    <flux:icon.pin class="h-5 w-5 shrink-0 text-brand-cyan" />
                    {{-- Valore pre-compilato: da spec Nunito SemiBold 18px #000000 (override della tipografia condivisa della pill) --}}
                    <flux:input wire:model="where" type="text" placeholder="Dove" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-lg [&_input]:!font-semibold [&_input]:!text-black [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                    <flux:label class="sr-only">Quando</flux:label>
                    <flux:icon.calendar class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model="when" type="text" placeholder="Quando" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                    <flux:label class="sr-only">Aggiungi ospiti</flux:label>
                    <flux:icon.team class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model="guests" type="text" placeholder="Aggiungi ospiti" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                    <flux:label class="sr-only">Animali</flux:label>
                    <flux:icon.animal class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model="animals" type="text" placeholder="Animali" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <flux:button type="submit" square aria-label="Cerca" class="!h-auto !w-auto shrink-0 !rounded-full !bg-brand-cyan !p-3.5 !text-white hover:!bg-brand-cyan-soft">
                    <flux:icon.search class="h-5 w-5" />
                </flux:button>
            </form>

            {{-- Filtri (XD: due pill dropdown "Componente 20"; comportamento dropdown TODO) --}}
            <p class="mt-10 text-lg font-semibold leading-6 text-black">Filtra la tua ricerca:</p>
            <div class="mt-[17px] flex items-center gap-[11px]">
                <flux:button class="!h-[30px] !gap-2 !rounded-full !border !border-[#C8C8C8] !bg-white !px-3.5 !text-sm !font-normal !text-[#555555] !shadow-none">
                    Tipologia
                    <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                </flux:button>
                <flux:button class="!h-[30px] !gap-2 !rounded-full !border !border-[#C8C8C8] !bg-white !px-3.5 !text-sm !font-normal !text-[#555555] !shadow-none">
                    Fascia di prezzo
                    <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                </flux:button>
            </div>

            {{-- Griglia risultati (XD: simbolo "Box hotel", 4 colonne × 3 righe) --}}
            <div class="mt-10 grid grid-cols-4 gap-x-[27px] gap-y-4">
                @foreach ($results as $result)
                    <article wire:key="res-{{ $loop->index }}" class="group relative flex flex-col rounded-[3px] border border-[#E9E9E9] bg-white">
                        <div class="relative m-2 overflow-hidden rounded-t-[3px]">
                            <img src="{{ asset('img/xd/'.$result['img'].'.jpg') }}" alt="{{ $result['name'] }}" class="aspect-[338/237] w-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute left-[10px] top-[15px] inline-flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white {{ $result['type'] === 'hotel' ? 'bg-[#FF9F3E]' : 'bg-[#FDC220]' }}">{{ $result['type'] === 'hotel' ? 'Hotel' : 'Servizi' }}</span>
                        </div>
                        <div class="flex flex-1 flex-col px-[18px] pt-[9px] pb-[18px]">
                            <p class="flex items-center gap-1.5 text-[13px] font-semibold tracking-[0.025em] text-[#555555]">
                                <flux:icon.pin class="h-[14px] w-4 shrink-0" />
                                {{ $result['location'] }}
                            </p>
                            <p class="mt-1.5 flex items-center gap-1.5 text-[13px] font-semibold tracking-[0.025em] text-[#555555]">
                                <flux:icon.star class="h-[15px] w-4 shrink-0" />
                                {{ $result['rating'] }}
                            </p>
                            <h3 class="mt-2.5 text-[20px] font-semibold leading-[25px] text-black">{{ $result['name'] }}</h3>
                            <p class="mt-auto pt-4 text-right text-[15px] font-normal text-[#627277]">A partire da <span class="whitespace-nowrap font-semibold tracking-[0.025em] text-[#0D171A]">0,00 €</span></p>
                        </div>
                        {{-- Link alla scheda: servizi → "Animal Holiday – Dettaglio servizio", hotel → "Animal Holiday – Dettaglio struttura" --}}
                        <a href="{{ $result['type'] === 'servizi' ? route('holiday.service', ['region' => $regionSlug, 'service' => $result['slug']]) : route('holiday.structure', ['region' => $regionSlug, 'structure' => $result['slug']]) }}" class="absolute inset-0 z-[1] rounded-[3px]" aria-label="{{ $result['name'] }}"></a>
                        {{-- Base bianca come !bg-[#fff] (non !bg-white): nel CSS compilato i valori arbitrari precedono !bg-brand-yellow, così il toggle vince --}}
                        <flux:button square x-data="{ fav: false }" @click="fav = !fav" ::class="fav && '!bg-brand-yellow'" ::aria-pressed="fav" aria-label="Aggiungi ai preferiti" class="!absolute !right-[18px] !top-[18px] !z-[2] !h-[30px] !w-[30px] !rounded-full !border-0 !bg-[#fff] !text-black !shadow-none">
                            <flux:icon.heart class="h-4 w-4" />
                        </flux:button>
                    </article>
                @endforeach
            </div>

            {{-- Paginazione (statica; pagina 1 attiva, prev disabilitato) --}}
            <nav class="mt-10 flex items-center justify-center gap-3" aria-label="Paginazione">
                <flux:button variant="ghost" square disabled aria-label="Pagina precedente" class="!h-auto !w-auto !p-1 !text-[#C8C8C8]">
                    <flux:icon.arrow-down class="h-4 w-4 rotate-90" />
                </flux:button>
                @foreach (range(1, 4) as $page)
                    <flux:button wire:key="page-{{ $page }}" square :aria-current="$page === 1 ? 'page' : null" class="!h-8 !w-8 !rounded-full !border-0 !text-base !font-medium !shadow-none {{ $page === 1 ? '!bg-black !text-white' : '!bg-white !text-black' }}">{{ $page }}</flux:button>
                @endforeach
                <flux:button variant="ghost" square aria-label="Pagina successiva" class="!h-auto !w-auto !p-1 !text-black">
                    <flux:icon.arrow-down class="h-4 w-4 -rotate-90" />
                </flux:button>
            </nav>
        </div>
    </main>

    @include('partials.site-footer')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />
</div>
