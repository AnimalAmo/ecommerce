{{-- Elenco delle pagine (design: is_legali). --}}
<div class="flex flex-col gap-[18px]">
    <x-admin.page-header :heading="__('admin-content.pages.title')" :sub="__('admin-content.pages.sub')">
        <x-slot:actions>
            <x-admin.button tone="primary" icon="plus" :href="route('admin.pages.create')" wire:navigate>{{ __('admin-content.pages.new') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.notice :heading="__('admin-content.pages.notice_heading')">
        {{ __('admin-content.pages.notice_body') }}
    </x-admin.notice>

    <x-admin.card>
        <x-admin.filters>
            <div class="min-w-[190px] max-w-[340px] flex-1">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('admin-content.pages.search_placeholder')" :aria-label="__('admin-content.pages.search_label')" />
            </div>
            <div class="min-w-[160px]">
                <flux:select wire:model.live="kind" :aria-label="__('admin-content.pages.filter_kind_label')">
                    <flux:select.option value="">{{ __('admin-content.pages.filter_kind_all') }}</flux:select.option>
                    @foreach (__('admin-content.pages.kind_filters') as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="min-w-[170px]">
                <flux:select wire:model.live="state" :aria-label="__('admin-content.pages.filter_state_label')">
                    <flux:select.option value="">{{ __('admin-content.pages.filter_state_all') }}</flux:select.option>
                    @foreach (__('admin-content.pages.state_filters') as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </x-admin.filters>

        @if ($rows->isEmpty())
            <x-admin.empty>{{ __('admin-content.common.no_results') }}</x-admin.empty>
        @else
            <div class="overflow-x-auto">
                <flux:table class="min-w-[900px]">
                    <flux:table.columns>
                        <flux:table.column class="!pl-5">{{ __('admin-content.pages.columns.page') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-content.pages.columns.address') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-content.pages.columns.kind') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-content.pages.columns.it') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-content.pages.columns.en') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-content.pages.columns.updated') }}</flux:table.column>
                        <flux:table.column align="end" class="!pr-5">{{ __('admin-content.common.actions') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($rows as $row)
                            <flux:table.row :key="$row['key']">
                                <flux:table.cell class="!pl-5">
                                    <a href="{{ $row['edit_url'] }}" wire:navigate class="text-[14.5px] font-bold text-admin-rail hover:text-admin-teal">{{ $row['name'] }}</a>
                                </flux:table.cell>
                                <flux:table.cell>{{ $row['path'] }}</flux:table.cell>
                                <flux:table.cell><x-admin.badge :tone="$kindTones[$row['kind']] ?? 'muted'">{{ __('admin-content.pages.kinds.'.$row['kind']) }}</x-admin.badge></flux:table.cell>
                                @foreach (['it', 'en'] as $lang)
                                    <flux:table.cell>
                                        <x-admin.badge :tone="$stateTones[$row['locales'][$lang]]">{{ __('admin-content.pages.states.'.$row['locales'][$lang]) }}</x-admin.badge>
                                    </flux:table.cell>
                                @endforeach
                                <flux:table.cell class="whitespace-nowrap">
                                    {{ $row['updated_at'] ? $row['updated_at']->locale('it')->translatedFormat('j M Y') : __('admin-content.common.never_modified') }}
                                </flux:table.cell>
                                <flux:table.cell class="!pr-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-admin.icon-action tone="edit" icon="pencil" :label="__('admin-content.common.edit')" :href="$row['edit_url']" wire:navigate />
                                        @if ($row['url'])
                                            <x-admin.icon-action tone="view" icon="arrow-top-right-on-square" :label="__('admin-content.common.view_on_site')" :href="$row['url']" target="_blank" />
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="px-5 py-3.5 text-[13.5px] text-gray-400">
                {{ trans_choice('admin-content.pages.count', $rows->count()) }}
            </div>
        @endif
    </x-admin.card>
</div>
