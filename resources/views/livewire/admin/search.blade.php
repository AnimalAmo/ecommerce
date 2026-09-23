@use('App\Enums\OrderStatus')
@use('App\Services\Admin\Search\GlobalSearch')
@use('App\Support\Format')

@php
    $link = 'text-[13px] font-bold text-admin-teal hover:underline';
    $shown = fn (int $count, int $total): ?string => $total > $count
        ? __('admin-dashboard.search.shown', ['shown' => $count, 'total' => $total])
        : null;
    $orderTones = [
        OrderStatus::Paid->value => 'success',
        OrderStatus::Confirmed->value => 'info',
        OrderStatus::Pending->value => 'warning',
        OrderStatus::Cancelled->value => 'muted',
    ];
@endphp

<div class="flex flex-col gap-[18px]">
    @if (! $searchable)
        <x-admin.page-header
            :heading="__('admin-dashboard.search.heading_empty')"
            :sub="__('admin-dashboard.search.hint', ['min' => GlobalSearch::MIN_LENGTH])"
        />
    @else
        <x-admin.page-header
            :heading="__('admin-dashboard.search.heading', ['term' => $term])"
            :sub="trans_choice('admin-dashboard.search.summary', $total, ['count' => $total])"
        />

        {{-- Schede --}}
        <x-admin.card :heading="__('admin-dashboard.search.catalog.heading')">
            <x-slot:aside>
                @if ($note = $shown($catalog['rows']->count(), $catalog['total']))
                    <span class="text-[12.5px] text-gray-400">{{ $note }}</span>
                @endif
                <a href="{{ route('admin.catalog.index', ['q' => $term]) }}" wire:navigate class="{{ $link }}">{{ __('admin-dashboard.search.catalog.all') }}</a>
            </x-slot:aside>

            @if ($catalog['rows']->isEmpty())
                <x-admin.empty>{{ __('admin-dashboard.search.catalog.empty') }}</x-admin.empty>
            @else
                <div class="overflow-x-auto">
                    <flux:table class="min-w-[max(100%,640px)]">
                        <flux:table.columns>
                            <flux:table.column class="!pl-5">{{ __('admin-dashboard.search.catalog.col_item') }}</flux:table.column>
                            <flux:table.column>{{ __('admin-dashboard.search.catalog.col_partner') }}</flux:table.column>
                            <flux:table.column>{{ __('admin-dashboard.search.catalog.col_type') }}</flux:table.column>
                            <flux:table.column class="!pr-5">{{ __('admin-dashboard.search.catalog.col_status') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($catalog['rows'] as $row)
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
                                    <flux:table.cell class="!pr-5"><x-admin.badge :tone="$row['statusTone']">{{ $row['statusLabel'] }}</x-admin.badge></flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </x-admin.card>

        {{-- Iscritti --}}
        <x-admin.card :heading="__('admin-dashboard.search.users.heading')">
            <x-slot:aside>
                @if ($note = $shown($users['items']->count(), $users['total']))
                    <span class="text-[12.5px] text-gray-400">{{ $note }}</span>
                @endif
                <a href="{{ route('admin.users.index', ['q' => $term]) }}" wire:navigate class="{{ $link }}">{{ __('admin-dashboard.search.users.all') }}</a>
            </x-slot:aside>

            @if ($users['items']->isEmpty())
                <x-admin.empty>{{ __('admin-dashboard.search.users.empty') }}</x-admin.empty>
            @else
                <div class="overflow-x-auto">
                    <flux:table class="min-w-[max(100%,560px)]">
                        <flux:table.columns>
                            <flux:table.column class="!pl-5">{{ __('admin-dashboard.search.users.col_user') }}</flux:table.column>
                            <flux:table.column>{{ __('admin-dashboard.search.users.col_role') }}</flux:table.column>
                            <flux:table.column class="!pr-5">{{ __('admin-dashboard.search.users.col_since') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($users['items'] as $user)
                                @php $isPartner = $user->roles->contains('name', 'partner'); @endphp
                                <flux:table.row :key="'user-'.$user->id">
                                    <flux:table.cell class="!pl-5">
                                        <a href="{{ route('admin.users.show', ['user' => $user->id]) }}" wire:navigate class="flex min-w-0 flex-col gap-[3px]">
                                            <span class="text-[15px] font-bold text-admin-rail">{{ $user->name }}</span>
                                            <span class="text-[12.5px] text-gray-400">{{ $user->email }}</span>
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <span class="flex flex-wrap gap-1.5">
                                            <x-admin.badge :tone="$isPartner ? 'purple' : 'info'">
                                                {{ __($isPartner ? 'admin-dashboard.search.users.role_partner' : 'admin-dashboard.search.users.role_client') }}
                                            </x-admin.badge>
                                            @if ($user->anonymized_at)
                                                <x-admin.badge>{{ __('admin-dashboard.search.users.anonymized') }}</x-admin.badge>
                                            @endif
                                        </span>
                                    </flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap !pr-5">{{ $user->created_at?->format('d/m/Y') }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </x-admin.card>

        {{-- Ordini --}}
        <x-admin.card :heading="__('admin-dashboard.search.orders.heading')">
            @if ($note = $shown($orders['items']->count(), $orders['total']))
                <x-slot:aside>
                    <span class="text-[12.5px] text-gray-400">{{ $note }}</span>
                </x-slot:aside>
            @endif

            @if ($orders['items']->isEmpty())
                <x-admin.empty>{{ __($searchesOrders ? 'admin-dashboard.search.orders.empty' : 'admin-dashboard.search.orders.empty_no_digits') }}</x-admin.empty>
            @else
                <div class="overflow-x-auto">
                    <flux:table class="min-w-[max(100%,640px)]">
                        <flux:table.columns>
                            <flux:table.column class="!pl-5">{{ __('admin-dashboard.search.orders.col_order') }}</flux:table.column>
                            <flux:table.column>{{ __('admin-dashboard.search.orders.col_buyer') }}</flux:table.column>
                            <flux:table.column>{{ __('admin-dashboard.search.orders.col_date') }}</flux:table.column>
                            <flux:table.column align="end">{{ __('admin-dashboard.search.orders.col_total') }}</flux:table.column>
                            <flux:table.column class="!pr-5">{{ __('admin-dashboard.search.orders.col_status') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($orders['items'] as $order)
                                <flux:table.row :key="'order-'.$order->id">
                                    <flux:table.cell class="!pl-5 font-bold !text-admin-rail">
                                        @if ($order->user_id)
                                            <a href="{{ route('admin.users.show', ['user' => $order->user_id]) }}" wire:navigate class="hover:text-admin-teal">{{ $order->order_number }}</a>
                                        @else
                                            {{ $order->order_number }}
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <span class="flex min-w-0 flex-col gap-[3px]">
                                            <span class="text-admin-rail">{{ trim($order->first_name.' '.$order->last_name) }}</span>
                                            <span class="text-[12.5px] text-gray-400">{{ $order->user_id ? $order->email : $order->email.' · '.__('admin-dashboard.search.orders.guest') }}</span>
                                        </span>
                                    </flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap">{{ $order->created_at?->format('d/m/Y') }}</flux:table.cell>
                                    <flux:table.cell align="end" class="whitespace-nowrap !text-admin-rail">{{ Format::money($order->total_cents) }}</flux:table.cell>
                                    <flux:table.cell class="!pr-5">
                                        <x-admin.badge :tone="$orderTones[$order->status->value] ?? 'muted'">{{ $order->status->label() }}</x-admin.badge>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </x-admin.card>
    @endif
</div>
