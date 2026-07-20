{{-- Card "Cosa troverai" dalla colonna json features: [{icon, title, lines[]}].
     Contratto: $features (array di card). --}}
{{-- Mobile (XD app "Dettaglio struttura"): griglia 2 colonne, card 136px alte --}}
<div class="mt-7 flex flex-wrap gap-3 max-lg:mt-4 max-lg:grid max-lg:grid-cols-2 max-lg:gap-4">
    @foreach ($features as $feature)
        <div wire:key="feature-{{ $loop->index }}" class="min-h-[164px] w-[215px] rounded-[4px] border border-[#DEDEDE] bg-white p-4 max-lg:min-h-[136px] max-lg:w-full max-lg:rounded-[3px] max-lg:p-3">
            @switch($feature['icon'])
                @case('bed')
                    <flux:icon.bed class="h-[18px] w-[18px]" />
                    @break

                @case('lunch')
                    <flux:icon.lunch class="h-[18px] w-[18px]" />
                    @break

                @case('spa')
                    <flux:icon.spa class="h-[18px] w-[18px]" />
                    @break
            @endswitch
            <p class="mt-[18px] text-[17px] font-medium text-[#2B2B2B] max-lg:mt-4 max-lg:text-[15px] max-lg:text-[#0D171A]">{{ $feature['title'] }}</p>
            <p class="mt-2 text-[17px] font-light leading-[25px] text-[#2B2B2B] max-lg:mt-1 max-lg:text-sm max-lg:font-normal max-lg:leading-[19px] max-lg:text-[#959595]">@foreach ($feature['lines'] as $line){{ $line }}@if (! $loop->last)<br>@endif
@endforeach</p>
        </div>
    @endforeach
</div>
