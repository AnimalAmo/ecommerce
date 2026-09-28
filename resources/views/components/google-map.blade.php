{{-- Mappa "Dove siamo": iframe Google Maps Embed con la chiave configurata, screenshot XD come fallback (vedi HasMapEmbed).
     Iframe taggato a mano per Iubenda (finalità 3 "Esperienza"): resta about:blank, col segnaposto sotto e la
     pill [data-map-pill] del chiamante nascosta (app.css), finché il consenso non gli dà data-suppressedsrc.
     wire:ignore perché ogni roundtrip Livewire riporterebbe l'iframe attivato al markup del server (about:blank),
     spegnendo la mappa dopo il consenso. --}}
@props(['query' => null, 'fallback' => null, 'alt' => ''])

@php $mapsKey = config('services.google.maps_key'); @endphp

@if (filled($mapsKey) && filled($query))
    <div wire:ignore data-map {{ $attributes->merge(['class' => 'relative w-full bg-gray-100']) }}>
        <div data-map-placeholder class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-gray-100 p-4 text-center">
            <flux:icon.pin class="h-6 w-6 shrink-0 text-gray-400" aria-hidden="true" />
            <p class="max-w-xs text-[13px] leading-5 text-gray-600">{{ __('maps.consent_notice') }}</p>
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-[13px] font-semibold text-ink">
                <a href="https://www.google.com/maps/search/?{{ http_build_query(['api' => 1, 'query' => $query]) }}" target="_blank" rel="noopener" class="underline underline-offset-2">{{ __('maps.open_in_google_maps') }}</a>
                {{-- Solo se la policy Iubenda offre la finalità 3: altrimenti il pannello non avrebbe nulla da attivare --}}
                <a href="#" class="iubenda-cs-preferences-link underline underline-offset-2" x-data x-cloak x-show="(window._iub?.csPurposes ?? []).map(String).includes('3')">{{ __('nav.footer.manage_cookies') }}</a>
            </div>
        </div>
        <iframe src="about:blank"
            data-suppressedsrc="https://www.google.com/maps/embed/v1/place?{{ http_build_query(['key' => $mapsKey, 'q' => $query, 'language' => 'it']) }}"
            data-iub-purposes="3"
            title="{{ $alt }}"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            {{-- Con un valore: ricostruendo l'iframe al consenso, Iubenda scarta gli attributi vuoti --}}
            allowfullscreen="allowfullscreen"
            class="_iub_cs_activate absolute inset-0 h-full w-full border-0"></iframe>
    </div>
@elseif ($fallback)
    <img src="{{ $fallback }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => 'w-full object-cover']) }}>
@endif
