{{-- Barra filtri in testa a una tabella: ricerca + select, a capo su schermi stretti. --}}
<div {{ $attributes->class('flex flex-wrap items-center gap-2.5 border-b border-gray-150 px-5 py-4') }}>
    {{ $slot }}
</div>
