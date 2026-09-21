{{-- Titolo di pagina del pannello: h1 + sottotitolo a sinistra, azioni a destra. --}}
@props(['heading', 'sub' => null])

<div {{ $attributes->class('flex flex-wrap items-end justify-between gap-3.5') }}>
    <div class="min-w-0">
        <h1 class="m-0 text-[25px] leading-tight font-bold text-admin-rail">{{ $heading }}</h1>
        @if (filled($sub))
            <p class="mt-2 text-[15px] text-gray-600">{{ $sub }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2.5">{{ $actions }}</div>
    @endisset
</div>
