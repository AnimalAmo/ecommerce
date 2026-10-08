{{-- «Scegli la camera» della scheda struttura (nessun artboard XD: stile delle
     card servizi della pagina). Una card per stanza con foto (+ «vedi tutte»,
     galleria partials.catalog.photo-gallery con un modale per stanza, incluso
     in fondo alla pagina), nome, tipologia, capienza, descrizione, servizi
     della camera, prezzo/notte e «Seleziona». La card scelta ha il bordo cyan.

     Parametri: $rooms (Room con amenities, solo con almeno due stanze),
     $room (stanza scelta), $structure (copertina di ripiego), $fullRoomIds
     (stanze piene nelle date scelte, solo partner Online: «Seleziona» spento). --}}
<section class="mt-10">
    <h2 class="text-[25px] font-bold leading-[30px] text-black max-lg:text-lg">{{ __('catalog.rooms.title') }}</h2>

    <div class="mt-5 space-y-4">
        @foreach ($rooms as $option)
            @php
                $isSelected = $room?->id === $option->id;
                $isFull = in_array($option->id, $fullRoomIds, true);
                $photos = $option->photoUrls();
                $amenities = [...$option->amenityRows('hotel'), ...$option->amenityRows('animal')];
                $typeLabel = $option->typeLabel();
            @endphp
            <article wire:key="room-{{ $option->id }}" data-room="{{ $option->id }}" class="flex gap-5 rounded-[4px] border bg-white p-4 max-md:flex-col {{ $isSelected ? 'border-brand-cyan ring-1 ring-brand-cyan' : 'border-[#DEDEDE]' }}">
                <div class="relative h-[185px] w-[278px] shrink-0 overflow-hidden rounded-[3px] max-md:w-full">
                    <img src="{{ $photos[0] ?? $structure->heroImageUrl() }}" alt="{{ $option->displayName() }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                    @include('partials.catalog.photo-gallery-trigger', [
                        'photos' => count($photos) > 1 ? $photos : [],
                        'label' => __('holiday.view_all_photos'),
                        'class' => '!absolute bottom-3 right-3',
                        'modalName' => 'room-gallery-'.$option->id,
                    ])
                </div>

                <div class="flex min-w-0 flex-1 flex-col">
                    <h3 class="text-lg font-bold leading-6 text-black">{{ $option->displayName() }}</h3>
                    @if ($typeLabel !== '' && $typeLabel !== $option->displayName())
                        <p class="mt-1 text-[13px] font-semibold text-[#555555]">{{ $typeLabel }}</p>
                    @endif

                    <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] font-semibold text-[#555555]">
                        <span class="flex items-center gap-[5px]">
                            <flux:icon.profile class="h-[13px] w-[13px] shrink-0" />
                            {{ __('catalog.rooms.max_guests', ['count' => $option->max_guests]) }}
                        </span>
                        <span class="flex items-center gap-[5px]">
                            <flux:icon.animal class="h-[15px] w-[15px] shrink-0" />
                            {{ __('catalog.rooms.max_animals', ['count' => $option->max_animals]) }}
                        </span>
                    </p>

                    @if (filled($option->description))
                        <p class="mt-3 text-[15px] leading-[22px] text-[#2B2B2B]">{{ $option->description }}</p>
                    @endif

                    @if ($amenities !== [])
                        <div class="mt-3">
                            <p class="text-[13px] font-bold text-[#0D171A]">{{ __('catalog.rooms.amenities') }}</p>
                            <ul class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1">
                                @foreach ($amenities as $amenity)
                                    <li wire:key="room-{{ $option->id }}-amenity-{{ $loop->index }}" class="flex items-center gap-2 text-[13px] text-[#0D171A]">
                                        <flux:icon.check class="h-3 w-3 shrink-0 text-[#37C443]" />
                                        {{ $amenity['label'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-4">
                        <p class="text-xl font-light text-[#2B2B2B]">{{ __('format.per_night', ['price' => \App\Support\Format::money($option->price_cents)]) }}</p>
                        @if ($isFull)
                            <p class="text-[13px] font-semibold text-brand-magenta">{{ __('catalog.rooms.full') }}</p>
                        @endif
                        @if ($isSelected)
                            <flux:button disabled icon="check" class="!h-[39px] !rounded-full !border-0 !bg-brand-cyan !px-6 !text-sm !font-bold !text-white !opacity-100 !shadow-none">{{ __('catalog.rooms.selected') }}</flux:button>
                        @elseif ($isFull)
                            <flux:button disabled class="!h-[39px] !rounded-full !border-0 !bg-gray-200 !px-6 !text-sm !font-bold !text-gray-400 !shadow-none">{{ __('catalog.rooms.select') }}</flux:button>
                        @else
                            <flux:button wire:click="selectRoom({{ $option->id }})" class="!h-[39px] !rounded-full !border-0 !bg-brand-yellow !px-6 !text-sm !font-bold !text-[#0D171A] !shadow-none">{{ __('catalog.rooms.select') }}</flux:button>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
