<div class="flex flex-col gap-[18px]">
    <x-admin.page-header
        heading="Catalogo"
        :sub="'Strutture, attività e Smartbox di tutti i partner. '.$totals['total'].' '.($totals['total'] === 1 ? 'scheda' : 'schede').', '.$totals['suspended'].' '.($totals['suspended'] === 1 ? 'sospesa' : 'sospese').'.'"
    >
        <x-slot:actions>
            <x-admin.button icon="arrow-down-tray" :href="$exportUrl">Esporta</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card>
        <x-admin.filters>
            <div class="min-w-[190px] max-w-[340px] flex-1">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Cerca per nome, luogo o partner" aria-label="Cerca nel catalogo" />
            </div>
            <div class="min-w-[150px]">
                <flux:select wire:model.live="partner" aria-label="Partner">
                    <flux:select.option value="">Tutti i partner</flux:select.option>
                    <flux:select.option value="platform">AnimalAmo</flux:select.option>
                    @foreach ($partners as $id => $label)
                        <flux:select.option value="{{ $id }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="min-w-[130px]">
                <flux:select wire:model.live="family" aria-label="Tipo">
                    <flux:select.option value="">Tutti i tipi</flux:select.option>
                    @foreach ($families as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="min-w-[140px]">
                <flux:select wire:model.live="region" aria-label="Regione">
                    <flux:select.option value="">Tutte le regioni</flux:select.option>
                    @foreach ($regions as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="min-w-[130px]">
                <flux:select wire:model.live="status" aria-label="Stato">
                    <flux:select.option value="">Tutti gli stati</flux:select.option>
                    @foreach ($statuses as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </x-admin.filters>

        @if ($rows->isEmpty())
            <x-admin.empty>Nessuna scheda corrisponde ai filtri.</x-admin.empty>
        @else
            <div class="overflow-x-auto">
                <flux:table class="min-w-[940px]">
                    <flux:table.columns>
                        <flux:table.column class="!pl-5">Scheda</flux:table.column>
                        <flux:table.column>Partner</flux:table.column>
                        <flux:table.column>Tipo</flux:table.column>
                        <flux:table.column>Regione</flux:table.column>
                        <flux:table.column align="end">Prezzo</flux:table.column>
                        <flux:table.column>Stato</flux:table.column>
                        <flux:table.column align="end" class="!pr-5">Azioni</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($rows as $row)
                            <flux:table.row :key="$row['key']">
                                <flux:table.cell class="!pl-5">
                                    <a href="{{ $row['url'] }}" wire:navigate class="flex items-center gap-3">
                                        <span aria-hidden="true" class="h-10 w-[52px] shrink-0 rounded-md bg-brand-cyan-bg bg-cover bg-center" @if ($row['img']) style="background-image: url('{{ $row['img'] }}')" @endif></span>
                                        <span class="flex min-w-0 flex-col gap-[3px]">
                                            <span class="text-[15px] font-bold text-admin-rail">{{ $row['name'] }}</span>
                                            <span class="flex items-center gap-1 text-[12.5px] text-gray-400"><flux:icon.pin class="size-3 shrink-0" />{{ $row['place'] }}</span>
                                        </span>
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell class="!text-admin-rail">{{ $row['partner'] }}</flux:table.cell>
                                <flux:table.cell><x-admin.badge :tone="$row['typeTone']">{{ $row['type'] }}</x-admin.badge></flux:table.cell>
                                <flux:table.cell>{{ $row['region'] ?? '—' }}</flux:table.cell>
                                <flux:table.cell align="end" class="whitespace-nowrap !text-admin-rail">{{ $row['price'] }}</flux:table.cell>
                                <flux:table.cell><x-admin.badge :tone="$row['statusTone']">{{ $row['statusLabel'] }}</x-admin.badge></flux:table.cell>
                                <flux:table.cell class="!pr-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-admin.icon-action tone="view" icon="eye" label="Apri scheda" :href="$row['url']" wire:navigate />
                                        <x-admin.icon-action
                                            tone="suspend"
                                            :icon="$row['suspended'] ? 'play' : 'pause'"
                                            :label="$row['suspended'] ? 'Riattiva' : 'Sospendi'"
                                            wire:click="askSuspend('{{ $row['family'] }}', {{ $row['id'] }})"
                                        />
                                        <x-admin.icon-action tone="delete" icon="trash" label="Elimina" wire:click="askDelete('{{ $row['family'] }}', {{ $row['id'] }})" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2.5 px-5 py-3.5">
                <span class="text-[13.5px] text-gray-400">{{ $page->count() }} di {{ $page->total() }} schede</span>
                <div>{{ $page->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-admin.card>

    @include('livewire.admin.catalog.partials.confirm-modal')
</div>
