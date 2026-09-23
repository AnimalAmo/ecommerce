<div class="flex flex-col gap-[18px]">
    <x-admin.page-header
        :heading="__('admin-catalog.index.heading')"
        :sub="__('admin-catalog.index.sub', [
            'items' => trans_choice('admin-catalog.index.items', $totals['total']),
            'suspended' => trans_choice('admin-catalog.index.suspended', $totals['suspended']),
        ])"
    >
        <x-slot:actions>
            <flux:modal.trigger name="catalog-create">
                <x-admin.button tone="primary" icon="plus">{{ __('admin-catalog.create.entry.new_button') }}</x-admin.button>
            </flux:modal.trigger>
            <x-admin.button icon="arrow-down-tray" :href="$exportUrl">{{ __('admin-catalog.index.export') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card fill>
        <x-admin.filters>
            <div class="min-w-[190px] max-w-[340px] flex-1">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('admin-catalog.index.search_placeholder')" :aria-label="__('admin-catalog.index.search_label')" />
            </div>
            <div class="min-w-[150px]">
                <flux:select wire:model.live="partner" :aria-label="__('admin-catalog.index.partner')">
                    <flux:select.option value="">{{ __('admin-catalog.index.all_partners') }}</flux:select.option>
                    <flux:select.option value="platform">{{ __('admin-catalog.platform') }}</flux:select.option>
                    @foreach ($partners as $id => $label)
                        <flux:select.option value="{{ $id }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="min-w-[130px]">
                <flux:select wire:model.live="family" :aria-label="__('admin-catalog.index.type')">
                    <flux:select.option value="">{{ __('admin-catalog.index.all_types') }}</flux:select.option>
                    @foreach ($families as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="min-w-[140px]">
                <flux:select wire:model.live="region" :aria-label="__('admin-catalog.index.region')">
                    <flux:select.option value="">{{ __('admin-catalog.index.all_regions') }}</flux:select.option>
                    @foreach ($regions as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="min-w-[130px]">
                <flux:select wire:model.live="status" :aria-label="__('admin-catalog.index.status')">
                    <flux:select.option value="">{{ __('admin-catalog.index.all_statuses') }}</flux:select.option>
                    @foreach ($statuses as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </x-admin.filters>

        @if ($rows->isEmpty())
            <x-admin.empty>{{ __('admin-catalog.index.empty') }}</x-admin.empty>
        @else
            <div class="flex-1 overflow-x-auto">
                <flux:table class="min-w-[max(100%,940px)]">
                    <flux:table.columns>
                        <flux:table.column class="!pl-5">{{ __('admin-catalog.index.columns.item') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-catalog.index.columns.partner') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-catalog.index.columns.type') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-catalog.index.columns.region') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('admin-catalog.index.columns.price') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-catalog.index.columns.status') }}</flux:table.column>
                        <flux:table.column align="end" class="!pr-5">{{ __('admin-catalog.index.columns.actions') }}</flux:table.column>
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
                                <flux:table.cell>{{ $row['region'] ?? __('admin.none') }}</flux:table.cell>
                                <flux:table.cell align="end" class="whitespace-nowrap !text-admin-rail">{{ $row['price'] }}</flux:table.cell>
                                <flux:table.cell><x-admin.badge :tone="$row['statusTone']">{{ $row['statusLabel'] }}</x-admin.badge></flux:table.cell>
                                <flux:table.cell class="!pr-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-admin.icon-action tone="view" icon="eye" :label="__('admin-catalog.index.open')" :href="$row['url']" wire:navigate />
                                        <x-admin.icon-action
                                            tone="suspend"
                                            :icon="$row['suspended'] ? 'play' : 'pause'"
                                            :label="__($row['suspended'] ? 'admin-catalog.index.reactivate' : 'admin-catalog.index.suspend')"
                                            wire:click="askSuspend('{{ $row['family'] }}', {{ $row['id'] }})"
                                        />
                                        <x-admin.icon-action tone="delete" icon="trash" :label="__('admin-catalog.index.delete')" wire:click="askDelete('{{ $row['family'] }}', {{ $row['id'] }})" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2.5 px-5 py-3.5">
                <span class="text-[13.5px] text-gray-400">{{ __('admin-catalog.index.paging', ['shown' => $page->count(), 'total' => $page->total()]) }}</span>
                <div>{{ $page->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-admin.card>

    @include('livewire.admin.catalog.partials.confirm-modal')
    @include('livewire.admin.catalog.partials.create-modal')
</div>
