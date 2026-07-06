{{-- Card "Cosa troverai" dalla colonna json features: [{icon, title, lines[]}].
     Contratto: $features (array di card). --}}
<div class="mt-7 flex flex-wrap gap-3">
    @foreach ($features as $feature)
        <div wire:key="feature-{{ $loop->index }}" class="min-h-[164px] w-[215px] rounded-[4px] border border-[#DEDEDE] bg-white p-4">
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
            <p class="mt-[18px] text-[17px] font-medium text-[#2B2B2B]">{{ $feature['title'] }}</p>
            <p class="mt-2 text-[17px] font-light leading-[25px] text-[#2B2B2B]">@foreach ($feature['lines'] as $line){{ $line }}@if (! $loop->last)<br>@endif
@endforeach</p>
        </div>
    @endforeach
</div>
