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
                    <article wire:key="box-{{ $box->id }}" class="group relative flex min-h-[391px] flex-col rounded-[3px] border border-[#E9E9E9] bg-white">
                        <div class="relative m-2 overflow-hidden rounded-t-[3px]">
                            <img src="{{ asset('img/xd/'.$box->img.'.jpg') }}" alt="{{ $box->title }}" class="aspect-[338/237] w-full object-cover transition duration-500 group-hover:scale-105">
                            {{-- Chip tag: label e colore dalla tassonomia ProductType (Soggiorno/Benessere/Avventura) --}}
                            <span class="absolute left-[10px] top-[10px] inline-flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $box->type->color() }}">{{ $box->type->label() }}</span>
                        </div>
                        <div class="flex flex-1 flex-col px-[18px] pt-2 pb-[14px]">
                            <h3 class="text-[20px] font-semibold leading-[25px] text-black">{{ $box->title }}</h3>
                            <p class="mt-[14px] flex items-center gap-1.5 text-[13px] font-semibold tracking-[0.025em] text-[#555555]">
                                <flux:icon.profile class="h-[14px] w-[14px] shrink-0" />
                                {{ $box->audience }}
                            </p>
                            <p class="mt-auto pt-4 text-right text-[15px] font-normal text-[#627277]">A partire da <span class="whitespace-nowrap font-semibold tracking-[0.025em] text-[#0D171A]">{{ \App\Support\Format::money($box->price_from_cents) }}</span></p>
                        </div>
                        <a href="{{ route('smartbox.detail', $box->slug) }}" class="absolute inset-0 z-[1] rounded-[3px]" aria-label="{{ $box->title }}"></a>
                        @include('partials.favorite-heart', ['type' => 'smartbox_package', 'id' => $box->id, 'active' => $this->isFavorite('smartbox_package', $box->id), 'classes' => '!absolute !right-[18px] !top-[18px] !z-[2]'])
                    </article>
                @endforeach
            </div>

            {{-- Paginazione (XD "Raggruppa 744"): pill reali del paginator, stile invariato --}}
            @if ($boxes->hasPages())
                <nav class="mt-10 flex items-center justify-center gap-3" aria-label="Paginazione">
                    @if ($boxes->onFirstPage())
                        <flux:button variant="ghost" square disabled aria-label="Pagina precedente" class="!h-auto !w-auto !p-1 !text-[#C8C8C8]">
                            <flux:icon.arrow-down class="h-4 w-4 rotate-90" />
                        </flux:button>
                    @else
                        <flux:button variant="ghost" square wire:click="previousPage" aria-label="Pagina precedente" class="!h-auto !w-auto !p-1 !text-black">
                            <flux:icon.arrow-down class="h-4 w-4 rotate-90" />
                        </flux:button>
                    @endif
                    @foreach (range(1, $boxes->lastPage()) as $page)
                        <flux:button wire:key="page-{{ $page }}" square wire:click="gotoPage({{ $page }})" :aria-current="$page === $boxes->currentPage() ? 'page' : null" class="!h-8 !w-8 !rounded-full !border-0 !text-base !font-medium !shadow-none {{ $page === $boxes->currentPage() ? '!bg-black !text-white' : '!bg-white !text-black' }}">{{ $page }}</flux:button>
                    @endforeach
                    @if ($boxes->hasMorePages())
                        <flux:button variant="ghost" square wire:click="nextPage" aria-label="Pagina successiva" class="!h-auto !w-auto !p-1 !text-black">
                            <flux:icon.arrow-down class="h-4 w-4 -rotate-90" />
                        </flux:button>
                    @else
                        <flux:button variant="ghost" square disabled aria-label="Pagina successiva" class="!h-auto !w-auto !p-1 !text-[#C8C8C8]">
                            <flux:icon.arrow-down class="h-4 w-4 -rotate-90" />
                        </flux:button>
                    @endif
                </nav>
            @endif
        </div>
    </main>

    @include('partials.site-footer')
</div>
