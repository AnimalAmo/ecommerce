{{-- Icona della riga meta della card prodotto, per $metaType (contratto FavoriteService::cards).
     $class = taglia/colore; star e calendar sono icone custom, user e clock heroicon (serve il "!"). --}}
@switch($metaType)
    @case('rating')
        <flux:icon.star class="{{ $class }} shrink-0" />
        @break
    @case('data')
        <flux:icon.calendar class="{{ $class }} shrink-0" />
        @break
    @case('persone')
        <flux:icon.user class="{{ $class }} shrink-0" />
        @break
    @case('durata')
        <flux:icon.clock class="{{ $class }} shrink-0" />
        @break
@endswitch
