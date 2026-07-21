{{-- Testata mobile delle sotto-pagine (XD app: gruppo "inietro" a y123 + titolo 18 semibold a y175).
     Riceve $backHref (default: la pagina Profilo) e $title; senza titolo resta il solo
     "Indietro" — è il caso del dettaglio post, dove il titolo è quello del post. --}}
@php
    $backHref ??= route('profilo');
    $title ??= null;
@endphp

<div class="lg:hidden">
    <a href="{{ $backHref }}" class="inline-flex items-center gap-2 text-sm leading-none text-[#959595]">
        <flux:icon.arrow-back class="h-4 w-4" />
        {{ __('profile.back') }}
    </a>

    @if ($title !== null)
        <h1 class="mt-4 text-lg font-semibold leading-none text-[#0D171A]">{{ $title }}</h1>
    @endif
</div>
