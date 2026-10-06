{{-- Attività ed Eventi, griglia delle regioni (cliente, 06/10/2026): stesse card e
     stesse foto di Animal Holiday; il clic porta alla lista della regione. --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pt-10 pb-20 max-lg:pt-6 max-lg:pb-10">
            <h1 class="text-lg font-bold text-[#0D171A] lg:text-4xl lg:text-black">{{ __('events.title') }}</h1>
            <p class="mt-2 text-[15px] text-[#555555] lg:mt-3 lg:text-[18px]">{{ __('events.subtitle') }}</p>

            {{-- "Dove" sulle regioni: stessa pill della ricerca di Animal Holiday, con il solo campo che qui filtra. --}}
            <form wire:submit="search" class="mt-8 flex w-full items-center gap-2 rounded-[100px] border border-[#F4F4F4] bg-white p-2 shadow-[1px_1px_10px_#0000001A] max-lg:mt-5">
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                    <flux:label class="sr-only">{{ __('events.regions_search') }}</flux:label>
                    <flux:icon.pin class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model="where" type="text" placeholder="{{ __('events.regions_search') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <flux:button type="submit" square aria-label="{{ __('events.search_cta') }}" class="!h-auto !w-auto shrink-0 !rounded-full !bg-brand-cyan !p-3.5 !text-white hover:!bg-brand-cyan-soft">
                    <flux:icon.search class="h-5 w-5" />
                </flux:button>
            </form>

            {{-- Catalogo ancora vuoto: stessa riga onesta della lista, invece di 20 card che promettono schede. --}}
            @if ($catalogueEmpty)
                <div class="mt-8 rounded-[4px] border border-[#E9E9E9] bg-gray-100 px-6 py-5">
                    <p class="text-lg font-semibold text-ink">{{ __('events.empty_catalogue_title') }}</p>
                    <p class="mt-1.5 text-[15px] leading-[22px] text-[#555555]">{{ __('events.empty_catalogue_body') }}</p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <flux:button :href="route('work-with-us')" class="!h-10 !rounded-full !border-0 !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink !shadow-none">{{ __('events.empty_catalogue_partner_cta') }}</flux:button>
                        <flux:button :href="route('news')" variant="ghost" class="!h-10 !rounded-full !px-6 !text-sm !font-bold !text-ink">{{ __('events.empty_catalogue_news_cta') }}</flux:button>
                    </div>
                </div>
            @endif

            @if ($regions->isEmpty())
                <p class="mt-10 text-lg text-[#555555]">{{ __('events.no_region') }}</p>
            @else
                <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 max-lg:mt-6 max-lg:gap-4">
                    @foreach ($regions as $region)
                        <a href="{{ route('eventi.region', ['region' => $region->slug]) }}" wire:key="reg-{{ $region->id }}" class="group block rounded-[3px] border border-[#E9E9E9] bg-white p-[10px]">
                            <div class="relative overflow-hidden">
                                <img src="{{ asset('img/xd/'.$region->img.'.jpg') }}" alt="{{ $region->name }}" class="h-80 w-full object-cover transition duration-500 group-hover:scale-105 max-lg:h-56">
                                <div class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/10 to-transparent"></div>
                                @if ($region->published_events_count > 0)
                                    <flux:badge class="absolute right-4 top-4 !rounded-[3px] !bg-brand-purple-soft !text-white">{{ trans_choice('events.events_count', $region->published_events_count, ['count' => $region->published_events_count]) }}</flux:badge>
                                @endif
                                <h2 class="absolute bottom-4 left-4 pr-4 text-[20px] font-bold text-white">{{ $region->name }}</h2>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    @include('partials.site-footer')
</div>
