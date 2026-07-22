{{-- Mappa "Dove siamo": iframe Google Maps Embed con la chiave configurata, screenshot XD come fallback (vedi HasMapEmbed). --}}
@props(['query' => null, 'fallback' => null, 'alt' => ''])

@php $mapsKey = config('services.google.maps_key'); @endphp

@if (filled($mapsKey) && filled($query))
    <iframe src="https://www.google.com/maps/embed/v1/place?{{ http_build_query(['key' => $mapsKey, 'q' => $query, 'language' => 'it']) }}"
        title="{{ $alt }}"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen
        {{ $attributes->merge(['class' => 'w-full border-0']) }}></iframe>
@elseif ($fallback)
    <img src="{{ $fallback }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => 'w-full object-cover']) }}>
@endif
