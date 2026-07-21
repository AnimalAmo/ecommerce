{{-- Card articolo mobile (XD app "Componente 30": riepilogo ordine e pop-up recensione).
     Foto 84x79 + tag/prezzo, titolo col pin, divider, date/ospiti/animali.
     La riga "luogo" non c'è: nell'artboard il pin accompagna il titolo.
     Riceve $item e, opzionale, $review (footer "Scrivi una recensione"). --}}
@php
    $review ??= false;
@endphp

<article class="w-full rounded-[3px] border border-[#E9E9E9] bg-white p-3">
    <div class="flex gap-[10px]">
        <img src="{{ $item['photo'] }}" alt="{{ $item['title'] }}" class="h-[79px] w-[84px] shrink-0 rounded-[2px] object-cover">

        <div class="flex min-w-0 flex-1 flex-col">
            <div class="flex items-start justify-between gap-2">
                <span class="flex h-[26px] items-center rounded-[3px] px-[10px] text-[13px] font-medium text-white" style="background-color: {{ $item['tagColor'] }}">{{ $item['tag'] }}</span>
                <span class="shrink-0 text-[15px] font-medium leading-[26px] text-[#0D171A]">{{ $item['price'] }}</span>
            </div>

            <div class="mt-[13px] flex items-center gap-2 text-[#0D171A]">
                <flux:icon.pin class="h-[11px] w-[11px] shrink-0" />
                <h2 class="truncate text-sm font-bold leading-none">{{ $item['title'] }}</h2>
            </div>
        </div>
    </div>

    <div class="mt-[13px] h-px bg-[#E9E9E9]" aria-hidden="true"></div>

    <div class="mt-[12px] space-y-[13px] text-[13px] leading-none text-[#0D171A]">
        @if ($item['dates'] !== null)
            <div class="flex items-center gap-2">
                <flux:icon.calendar class="!h-[11px] !w-[11px] shrink-0" />
                <span class="truncate">{{ $item['dates'] }}</span>
            </div>
        @endif
        @if ($item['guests'] !== null)
            <div class="flex items-center gap-2">
                <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                <span class="truncate">{{ $item['guests'] }}</span>
            </div>
        @endif
        @if ($item['animals'] !== null)
            <div class="flex items-center gap-2">
                <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                <span class="truncate">{{ $item['animals'] }}</span>
            </div>
        @endif
    </div>

    @if ($review)
        <div class="mt-[15px] h-px bg-[#E9E9E9]" aria-hidden="true"></div>

        <flux:button variant="ghost" icon="pencil" icon:variant="outline" wire:click="openReview({{ $item['id'] }})" class="!mt-[10px] !h-auto !w-full !gap-[5px] !p-0 !text-sm !font-medium !text-[#2B2B2B] hover:!bg-transparent [&_svg]:!size-[14px]">{{ __('profile.write_review') }}</flux:button>
    @endif
</article>
