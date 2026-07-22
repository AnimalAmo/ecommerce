{{-- Righe "Informazioni generali" dalla colonna json general_info: [{icon, title, lines[]}].
     Contratto: $rows (array di righe). Icone e taglie come da XD per tipo di riga. --}}
<ul class="mt-4 space-y-4">
    @foreach ($rows as $row)
        <li wire:key="info-{{ $loop->index }}" class="flex items-start gap-3.5">
            @switch($row['icon'])
                @case('calendar-return')
                    <flux:icon.calendar-return class="mt-0.5 h-[15px] w-[15px] shrink-0 text-[#0D171A]" />
                    @break

                @case('coffee')
                    <flux:icon.coffee class="h-[18px] w-[18px] shrink-0" />
                    @break

                @case('lunch')
                    <flux:icon.lunch class="h-[18px] w-[18px] shrink-0" />
                    @break

                @case('home')
                    <flux:icon.home class="h-[19px] w-[19px] shrink-0 text-[#0D171A]" />
                    @break
            @endswitch
            <div>
                <p class="text-[15px] font-medium text-[#0D171A]">{{ $row['title'] }}</p>
                {{-- Mobile (XD app): dettaglio 14px #959595 — nascosto senza righe (righe partner e copy in attesa) --}}
                @if (! empty($row['lines']))
                    <p class="mt-1 max-w-[613px] text-[15px] text-[#555555] max-lg:text-sm max-lg:text-[#959595]">@foreach ($row['lines'] as $line){{ $line }}@if (! $loop->last)<br>@endif
@endforeach</p>
                @endif
            </div>
        </li>
    @endforeach
</ul>
