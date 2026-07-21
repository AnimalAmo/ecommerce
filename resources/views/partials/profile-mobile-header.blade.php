{{-- Testata mobile delle sotto-pagine profilo (XD app: gruppo "inietro" a y123 + titolo 18 semibold a y175).
     Riceve $title e, opzionale, $backHref (default: la pagina Profilo). --}}
@php
    $backHref ??= route('profilo');
@endphp

<div class="lg:hidden">
    <a href="{{ $backHref }}" aria-label="{{ __('profile.back') }}" class="inline-flex text-[#0D171A]">
        <flux:icon.arrow-back class="h-4 w-4" />
    </a>

    <h1 class="mt-9 text-lg font-semibold leading-none text-[#0D171A]">{{ $title }}</h1>
</div>
