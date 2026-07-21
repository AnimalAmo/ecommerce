{{-- Testata mobile delle sotto-pagine profilo (XD app: gruppo "inietro" a y123 + titolo 18 semibold a y175).
     Riceve $title e, opzionale, $backHref (default: la pagina Profilo). --}}
@php
    $backHref ??= route('profilo');
@endphp

<div class="lg:hidden">
    <a href="{{ $backHref }}" class="inline-flex items-center gap-2 text-sm leading-none text-[#959595]">
        <flux:icon.arrow-back class="h-4 w-4" />
        {{ __('profile.back') }}
    </a>

    <h1 class="mt-4 text-lg font-semibold leading-none text-[#0D171A]">{{ $title }}</h1>
</div>
