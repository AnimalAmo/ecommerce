{{--
    Copertina di un articolo di Animal Times (media collection `cover`).
    `conversion`: card (griglie) o hero (pagina dell'articolo). Le classi di
    dimensione passano dal chiamante; senza copertina resta un riquadro colore
    della stessa misura, così la griglia non salta.
--}}
@props(['article', 'conversion' => 'card'])

@php $coverUrl = $article->coverUrl($conversion); @endphp

@if ($coverUrl)
    <img src="{{ $coverUrl }}" alt="{{ $article->coverAlt() }}" {{ $attributes }}>
@else
    <div aria-hidden="true" {{ $attributes->class('bg-brand-cyan-bg') }}></div>
@endif
