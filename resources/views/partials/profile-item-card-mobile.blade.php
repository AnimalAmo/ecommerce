{{-- Card articolo mobile (XD app "Componente 30" 343x243: riepilogo ordine, scrivi recensione, sweet alert).
     Foto 84x64 + tag/prezzo, titolo col pin, divider, date/ospiti/animali, pillola recensione 319x39.
     La riga "luogo" non c'è: nell'artboard il pin accompagna il titolo.
     Riceve $item e, opzionali: $review (pillola "Scrivi una recensione"), $accent (colore divider),
     $plain (senza cornice: nello sweet alert la card sta nuda sul pannello verde). --}}
@php
    $review ??= false;
    $accent ??= '#E9E9E9';
    $plain ??= false;
@endphp

<article class="w-full {{ $plain ? '' : 'rounded-[3px] border border-[#E9E9E9] bg-white p-3' }} {{ $review ? 'pb-[15px]' : '' }}">
    <div class="flex gap-[10px]">
        <img src="{{ $item['photo'] }}" alt="{{ $item['title'] }}" class="h-16 w-[84px] shrink-0 rounded-[3px] object-cover">

        {{-- Colonna destra allineata al tag (y18), non al bordo foto (y12) --}}
        <div class="flex min-w-0 flex-1 flex-col pt-[6px]">
            <div class="flex items-start justify-between gap-2">
                <span class="flex h-[26px] items-center rounded-[3px] px-[10px] text-[13px] font-medium text-white" style="background-color: {{ $item['tagColor'] }}">{{ $item['tag'] }}</span>
                <span class="shrink-0 text-[15px] font-medium leading-[26px] text-[#0D171A]">{{ $item['price'] }}</span>
            </div>

            <div class="mt-4 flex items-center gap-[7px] text-[#0D171A]">
                <flux:icon.pin class="h-[11px] w-[11px] shrink-0" />
                <h2 class="truncate text-sm font-bold leading-none">{{ $item['title'] }}</h2>
            </div>
        </div>
    </div>

    <div class="mt-[15px] h-px" style="background-color: {{ $accent }}" aria-hidden="true"></div>

    <div class="mt-[15px] space-y-[13px] text-[13px] leading-none text-[#0D171A]">
        @if ($item['dates'] !== null)
            <div class="flex items-center gap-[10px]">
                <flux:icon.calendar class="!h-[11px] !w-[11px] shrink-0" />
                <span class="truncate">{{ $item['dates'] }}</span>
            </div>
        @endif
        @if ($item['guests'] !== null)
            <div class="flex items-center gap-[10px]">
                <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                <span class="truncate">{{ $item['guests'] }}</span>
            </div>
        @endif
        @if ($item['animals'] !== null)
            <div class="flex items-center gap-[10px]">
                <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                <span class="truncate">{{ $item['animals'] }}</span>
            </div>
        @endif
    </div>

    @if ($review)
        {{-- XD: pillola grigia piena (319x39 r20 #F4F4F4), non un link testuale --}}
        <flux:button variant="ghost" icon="pencil" icon:variant="outline" wire:click="openReview({{ $item['id'] }})" class="!mt-[18px] !h-[39px] !w-full !gap-[7px] !rounded-[20px] !bg-[#F4F4F4] !text-sm !font-bold !text-[#0D171A] hover:!bg-[#EAEAEA] [&_svg]:!size-[14px]">{{ __('profile.write_review') }}</flux:button>
    @endif
</article>
