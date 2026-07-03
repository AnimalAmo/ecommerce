{{-- Smartbox (XD: "Smartbox") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pt-10 pb-[120px]">
            <h1 class="text-4xl font-bold text-black">Smartbox</h1>
            <p class="mt-4 max-w-[1295px] text-lg leading-6 text-black">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>

            {{-- Filtri (XD: quattro pill dropdown "Componente 20"; comportamento dropdown TODO) --}}
            <p class="mt-10 text-lg font-semibold leading-6 text-black">Filtra la tua ricerca:</p>
            <div class="mt-[17px] flex flex-wrap items-center gap-[11px]">
                @foreach (['Tipologia', 'Fascia di prezzo', 'Numero di persone', 'Luogo'] as $filter)
                    <flux:button wire:key="filter-{{ $loop->index }}" class="!h-[30px] !gap-2 !rounded-full !border !border-[#C8C8C8] !bg-white !px-3.5 !text-sm !font-normal !text-[#555555] !shadow-none">
                        {{ $filter }}
                        <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                    </flux:button>
                @endforeach
            </div>

            {{-- Griglia cofanetti (XD: simbolo "Box smartbox" 354x391, 4 colonne × 3 righe) --}}
            <div class="mt-[29px] grid grid-cols-4 gap-x-[27px] gap-y-6">
                @foreach ($boxes as $box)
                    @php
                        // Colori tag da XD: Soggiorno #8DE0FF, Benessere #8DABFF, Avventura #3E72FF
                        $tagClass = match ($box['tag']) {
                            'Soggiorno' => 'bg-[#8DE0FF]',
                            'Benessere' => 'bg-[#8DABFF]',
                            default => 'bg-[#3E72FF]',
                        };
                    @endphp
                    <article wire:key="box-{{ $loop->index }}" class="group relative flex min-h-[391px] flex-col rounded-[3px] border border-[#E9E9E9] bg-white">
                        <div class="relative m-2 overflow-hidden rounded-t-[3px]">
                            <img src="{{ asset('img/xd/'.$box['img'].'.jpg') }}" alt="{{ $box['title'] }}" class="aspect-[338/237] w-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute left-[10px] top-[10px] inline-flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white {{ $tagClass }}">{{ $box['tag'] }}</span>
                        </div>
                        <div class="flex flex-1 flex-col px-[18px] pt-2 pb-[14px]">
                            <h3 class="text-[20px] font-semibold leading-[25px] text-black">{{ $box['title'] }}</h3>
                            <p class="mt-[14px] flex items-center gap-1.5 text-[13px] font-semibold tracking-[0.025em] text-[#555555]">
                                <flux:icon.profile class="h-[14px] w-[14px] shrink-0" />
                                {{ $box['audience'] }}
                            </p>
                            <p class="mt-auto pt-4 text-right text-[15px] font-normal text-[#627277]">A partire da <span class="whitespace-nowrap font-semibold tracking-[0.025em] text-[#0D171A]">0,00 €</span></p>
                        </div>
                        <a href="{{ route('smartbox.detail', $box['slug']) }}" class="absolute inset-0 z-[1] rounded-[3px]" aria-label="{{ $box['title'] }}"></a>
                        <flux:button square aria-label="Aggiungi ai preferiti" class="!absolute !right-[18px] !top-[18px] !z-[2] !h-[30px] !w-[30px] !rounded-full !border-0 !bg-white !text-black !shadow-none">
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
