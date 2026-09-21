{{--
    Schede sottolineate del design ("Messaggi 5 | Candidature 14").
    items: list<array{key: string, label: string, count?: int|null}>; `current` = chiave attiva;
    `model` = proprietà Livewire impostata al click.
--}}
@props(['items', 'current', 'model'])

<div {{ $attributes->class('flex gap-7 overflow-x-auto border-b border-gray-150') }} role="tablist">
    @foreach ($items as $tab)
        @php $active = $tab['key'] === $current; @endphp
        <flux:button
            variant="ghost"
            wire:click="$set('{{ $model }}', '{{ $tab['key'] }}')"
            wire:key="tab-{{ $model }}-{{ $tab['key'] }}"
            role="tab"
            aria-selected="{{ $active ? 'true' : 'false' }}"
            @class([
                '!h-auto !rounded-none !border-0 !border-b-2 !bg-transparent !px-0 !pt-3.5 !pb-3 !text-[14.5px] !shadow-none',
                '[&>span]:flex [&>span]:items-center [&>span]:gap-2',
                '!border-b-[#68CDEB] !font-bold !text-admin-rail' => $active,
                '!border-b-transparent !font-semibold !text-gray-600' => ! $active,
            ])
        >
            {{ $tab['label'] }}
            @if (isset($tab['count']))
                <span @class([
                    'inline-flex h-5 min-w-[21px] items-center justify-center rounded-full px-1.5 text-[11.5px] font-bold',
                    'bg-brand-cyan-bg text-admin-teal' => $active,
                    'bg-admin-row text-gray-400' => ! $active,
                ])>{{ $tab['count'] }}</span>
            @endif
        </flux:button>
    @endforeach
</div>
