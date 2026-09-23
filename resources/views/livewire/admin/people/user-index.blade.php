@php
    use App\Services\Admin\People\UserDirectory;

    $th = '!text-[13.5px] !font-semibold !text-gray-400';
    $initials = fn ($u) => mb_strtoupper(mb_substr((string) $u->first_name, 0, 1).mb_substr((string) $u->last_name, 0, 1));
    $count = fn (int $n) => number_format($n, 0, ',', '.');
    $sub = trans_choice('admin-people.users.subtitle_users', $totals['users'], ['count' => $count($totals['users'])])
        .' '.trans_choice('admin-people.users.subtitle_newsletter', $totals['newsletter'], ['count' => $count($totals['newsletter'])]);
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.page-header :heading="__('admin-people.users.title')" :sub="$sub">
        <x-slot:actions>
            <x-admin.button tone="outline" icon="arrow-down-tray" :href="route('admin.users.export', $exportQuery)">{{ __('admin-people.users.export') }}</x-admin.button>
            <x-admin.button tone="primary" icon="plus" :href="route('admin.users.create')" wire:navigate>{{ __('admin-people.partner_create.new_button') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card fill>
        <x-admin.filters>
            <flux:input
                wire:model.live.debounce.300ms="q"
                icon="magnifying-glass"
                type="search"
                :placeholder="__('admin-people.users.search')"
                :aria-label="__('admin-people.users.search')"
                class="min-w-[190px] flex-1 sm:max-w-[340px]"
            />
            <flux:select wire:model.live="newsletter" :aria-label="__('admin-people.users.filter_newsletter')" class="!w-auto min-w-[170px]">
                <flux:select.option value="all">{{ __('admin-people.users.newsletter_all') }}</flux:select.option>
                <flux:select.option value="with">{{ __('admin-people.users.newsletter_with') }}</flux:select.option>
                <flux:select.option value="without">{{ __('admin-people.users.newsletter_without') }}</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="status" :aria-label="__('admin-people.users.filter_status')" class="!w-auto min-w-[150px]">
                <flux:select.option value="all">{{ __('admin-people.users.status_all') }}</flux:select.option>
                <flux:select.option value="active">{{ __('admin-people.users.status.active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('admin-people.users.status.inactive') }}</flux:select.option>
                <flux:select.option value="anonymized">{{ __('admin-people.users.status.anonymized') }}</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="role" :aria-label="__('admin-people.users.filter_role')" class="!w-auto min-w-[140px]">
                <flux:select.option value="all">{{ __('admin-people.users.role_all') }}</flux:select.option>
                <flux:select.option value="client">{{ __('admin-people.users.role_clients') }}</flux:select.option>
                <flux:select.option value="partner">{{ __('admin-people.users.role_partners') }}</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="period" :aria-label="__('admin-people.users.filter_period')" class="!w-auto min-w-[160px]">
                <flux:select.option value="always">{{ __('admin-people.users.period_always') }}</flux:select.option>
                <flux:select.option value="30d">{{ __('admin-people.users.period_30d') }}</flux:select.option>
                <flux:select.option value="year">{{ __('admin-people.users.period_year') }}</flux:select.option>
            </flux:select>
        </x-admin.filters>

        @if ($users->isEmpty())
            <x-admin.empty>{{ __('admin-people.users.empty') }}</x-admin.empty>
        @else
            <div class="flex-1 overflow-x-auto">
                <flux:table class="min-w-[max(100%,900px)]">
                    <flux:table.columns>
                        <flux:table.column :class="$th.' !pl-5'" sortable :sorted="$sort === 'name'" :direction="$dir" wire:click="sortBy('name')">{{ __('admin-people.users.col_user') }}</flux:table.column>
                        <flux:table.column :class="$th" sortable :sorted="$sort === 'created_at'" :direction="$dir" wire:click="sortBy('created_at')">{{ __('admin-people.users.col_since') }}</flux:table.column>
                        <flux:table.column :class="$th">{{ __('admin-people.users.col_newsletter') }}</flux:table.column>
                        <flux:table.column :class="$th" align="end" sortable :sorted="$sort === 'orders'" :direction="$dir" wire:click="sortBy('orders')">{{ __('admin-people.users.col_orders') }}</flux:table.column>
                        <flux:table.column :class="$th" align="end" sortable :sorted="$sort === 'spent'" :direction="$dir" wire:click="sortBy('spent')">{{ __('admin-people.users.col_spent') }}</flux:table.column>
                        <flux:table.column :class="$th">{{ __('admin-people.users.col_status') }}</flux:table.column>
                        <flux:table.column :class="$th.' !pr-5'" align="end">{{ __('admin-people.users.col_actions') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($users as $u)
                            @php $isPartner = $u->roles->contains('name', 'partner'); @endphp
                            <flux:table.row :key="$u->id">
                                <flux:table.cell class="!pl-5">
                                    <a href="{{ route('admin.users.show', $u) }}" wire:navigate class="flex items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-cyan-bg text-[12.5px] font-bold text-admin-teal">{{ $initials($u) }}</span>
                                        <span class="flex min-w-0 flex-col gap-0.5">
                                            <span class="flex items-center gap-2">
                                                <span class="text-[15px] font-bold text-admin-rail">{{ $u->name }}</span>
                                                <x-admin.badge :tone="$isPartner ? 'purple' : 'muted'">{{ __('admin-people.role.'.($isPartner ? 'partner' : 'client')) }}</x-admin.badge>
                                            </span>
                                            <span class="text-[12.5px] text-gray-400">{{ $u->email }}</span>
                                        </span>
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell class="!text-gray-600">{{ $u->created_at?->locale('it')->isoFormat('D MMM YYYY') }}</flux:table.cell>
                                <flux:table.cell>
                                    @php $nl = in_array($u->newsletter_state, ['confirmed', 'pending'], true) ? $u->newsletter_state : 'none'; @endphp
                                    <x-admin.badge :tone="['confirmed' => 'success', 'pending' => 'warning', 'none' => 'muted'][$nl]">{{ __('admin-people.users.newsletter.'.$nl) }}</x-admin.badge>
                                </flux:table.cell>
                                <flux:table.cell align="end">{{ (int) $u->paid_orders_count }}</flux:table.cell>
                                <flux:table.cell align="end">{{ UserDirectory::money((int) $u->spent_cents) }}</flux:table.cell>
                                <flux:table.cell>
                                    @php $state = UserDirectory::status($u); @endphp
                                    <x-admin.badge :tone="['active' => 'info', 'inactive' => 'warning', 'anonymized' => 'muted'][$state]">{{ __('admin-people.users.status.'.$state) }}</x-admin.badge>
                                </flux:table.cell>
                                <flux:table.cell align="end" class="!pr-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-admin.icon-action tone="view" icon="eye" :label="__('admin-people.users.open')" :href="route('admin.users.show', $u)" wire:navigate />
                                        @if ($u->anonymized_at === null)
                                            <x-admin.icon-action tone="delete" icon="trash" :label="__('admin-people.users.anonymize')" wire:click="askAnonymize({{ $u->id }})" />
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="px-5 py-3.5">
                <flux:pagination :paginator="$users" />
            </div>
        @endif
    </x-admin.card>

    @include('livewire.admin.people.partials.anonymize-modal')
</div>
