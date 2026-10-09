{{--
    Elenco card stanza (ManagesRoomRows), condiviso fra lo step 5 del wizard e
    la creazione struttura del pannello. Variabili:
    $roomRows (righe del HotelRoomsForm), $whole (alloggio intero),
    $roomTypes (slug => label), $rowsErrorKey (chiave d'errore dell'elenco),
    $pendingUploads (pannello: foto in upload per chiave stanza).
--}}
@php
    $pendingUploads = $pendingUploads ?? [];
@endphp

<div class="flex flex-col gap-3">
    @forelse ($roomRows as $i => $room)
        @php
            $key = (string) ($room['key'] ?? '');
            $typeLabel = $whole ? __('partner.hotel_rooms.type_whole') : ($roomTypes[$room['type'] ?? ''] ?? '');
            $title = trim((string) ($room['name']['it'] ?? '')) ?: $typeLabel;
            $cover = $room['photos'][0] ?? null;
            $pending = collect($pendingUploads[$key] ?? [])->first(fn ($file) => is_object($file) && method_exists($file, 'isPreviewable') && $file->isPreviewable());
            $complete = filled($room['price'] ?? null) && (int) ($room['max_guests'] ?? 0) > 0;
        @endphp
        <div class="flex flex-col gap-3 rounded-[6px] border border-[#E2EAEB] bg-white p-3 sm:flex-row sm:items-center" wire:key="room-card-{{ $key ?: $i }}">
            <div class="h-[72px] w-[96px] shrink-0 overflow-hidden rounded-[4px] bg-[#F4F4F4]">
                @if ($cover)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cover) }}" alt="" class="h-full w-full object-cover">
                @elseif ($pending)
                    <img src="{{ $pending->temporaryUrl() }}" alt="" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-[#C8C8C8]"><flux:icon.photo class="h-6 w-6" /></div>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate text-[15px] font-bold text-[#0D171A]">{{ $title ?: __('partner.hotel_rooms.incomplete') }}</p>
                @if ($title !== $typeLabel && $typeLabel !== '')
                    <p class="text-[13px] text-[#627277]">{{ $typeLabel }}</p>
                @endif
                @if ($complete)
                    <p class="mt-1 text-[13px] text-[#2B2B2B]">
                        {{ __('partner.hotel_rooms.card_price', ['price' => $room['price']]) }}
                        · {{ __('partner.hotel_rooms.card_guests', ['guests' => (int) $room['max_guests'], 'animals' => (int) ($room['max_animals'] ?? 0)]) }}
                        @unless ($whole)
                            · {{ __('partner.hotel_rooms.card_units', ['count' => (int) ($room['units'] ?? 1)]) }}
                        @endunless
                    </p>
                @else
                    <p class="mt-1 text-[13px] font-medium text-[#F85933]">{{ __('partner.hotel_rooms.incomplete') }}</p>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-1">
                @unless ($whole)
                    <flux:button type="button" size="sm" variant="ghost" square icon="chevron-up" wire:click="moveRoom({{ $i }}, -1)" :disabled="$loop->first" :aria-label="__('partner.hotel_rooms.move_up')" />
                    <flux:button type="button" size="sm" variant="ghost" square icon="chevron-down" wire:click="moveRoom({{ $i }}, 1)" :disabled="$loop->last" :aria-label="__('partner.hotel_rooms.move_down')" />
                @endunless
                <flux:button type="button" size="sm" variant="ghost" icon="pencil-square" wire:click="openRoom({{ $i }})">{{ __('partner.hotel_rooms.edit') }}</flux:button>
                @unless ($whole)
                    <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeRoom({{ $i }})" wire:confirm="{{ __('partner.hotel_rooms.delete_confirm') }}" class="!text-[#F85933]">{{ __('partner.hotel_rooms.delete') }}</flux:button>
                @endunless
            </div>
        </div>
    @empty
        <p class="text-[14px] text-[#959595]">{{ __('partner.hotel_rooms.empty') }}</p>
    @endforelse

    <flux:error :name="$rowsErrorKey" />
    {{-- Una riga che non passa (es. salvata da una bozza vecchia): l'errore si vede qui, la card dice quale. --}}
    <flux:error :name="$rowsErrorKey.'.*'" />

    @unless ($whole)
        <div>
            <flux:button type="button" wire:click="openRoom" variant="ghost" icon="plus" class="!-ml-1 !px-1 !text-[16px] !font-bold !text-brand-cyan hover:!bg-transparent [&_svg]:!text-brand-cyan">{{ __('partner.hotel_rooms.add_room') }}</flux:button>
        </div>
    @endunless
</div>
