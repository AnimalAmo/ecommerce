{{-- Riquadro bianco del pannello. Con `heading` ha la testata con titolo e uno slot `aside`; con `fill` occupa l'altezza che resta nella pagina. --}}
@props(['heading' => null, 'fill' => false])

<section {{ $attributes->class(['min-w-0 rounded-[10px] border border-gray-150 bg-white', 'flex flex-1 flex-col' => $fill]) }}>
    @if (filled($heading) || isset($aside))
        <header class="flex items-center justify-between gap-3 border-b border-gray-150 px-5 py-4">
            <h2 class="m-0 text-base font-bold text-admin-rail">{{ $heading }}</h2>
            @isset($aside)
                <div class="flex items-center gap-2">{{ $aside }}</div>
            @endisset
        </header>
    @endif
    {{ $slot }}
</section>
