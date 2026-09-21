@use('App\Services\Admin\Money\Period')
@use('App\Support\Format')
@use('Illuminate\Support\Str')

@php
    $month = $current->monthName();
    $grossLabel = $current->isMonth()
        ? __(preg_match('/^[aeiou]/i', $month) === 1 ? 'admin-money.kpi.gross_month_vowel' : 'admin-money.kpi.gross_month', ['month' => $month])
        : __('admin-money.kpi.gross_range');

    $partnersNote = match (true) {
        $totals['provisional'] > 0 => trans_choice('admin-money.kpi.partners_provisional', $totals['provisional'], ['count' => $totals['provisional']]),
        $totals['partnersGross'] > 0 => __('admin-money.kpi.partners_note', ['percent' => (int) round($totals['partners'] * 100 / $totals['partnersGross'])]),
        default => __('admin-money.kpi.empty_note'),
    };

    $platformNote = $totals['directSales'] > 0
        ? __('admin-money.kpi.platform_direct', ['amount' => Format::money($totals['directSales'])])
        : __('admin-money.kpi.platform_note');

    $releaseNote = match (true) {
        $totals['toRelease'] === 0 => __('admin-money.kpi.to_release_none'),
        $totals['nextRelease'] === null => __('admin-money.kpi.to_release_failed'),
        $totals['nextRelease']->isFuture() => __('admin-money.kpi.to_release_next', ['date' => Period::formatDay($totals['nextRelease'])]),
        default => __('admin-money.kpi.to_release_due', ['date' => Period::formatDay($totals['nextRelease'])]),
    };

    $stateTones = [
        'released' => 'success',
        'scheduled' => 'info',
        'waiting' => 'warning',
        'retrying' => 'warning',
        'failed' => 'danger',
    ];
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.page-header :heading="__('admin-money.title')" :sub="__('admin-money.subtitle')">
        <x-slot:actions>
            <div class="min-w-[170px]">
                <flux:select wire:model.live="period" aria-label="{{ __('admin-money.period_label') }}" class="!appearance-auto">
                    @foreach ($options as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <x-admin.button icon="arrow-down-tray" :href="$exportUrl">{{ __('admin-money.export') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($stuck['groups'] > 0)
        <x-admin.notice tone="warning" :heading="trans_choice('admin-money.notice.stuck_heading', $stuck['groups'], ['count' => $stuck['groups']])">
            {{ __('admin-money.notice.stuck_body', ['amount' => Format::money($stuck['amount'])]) }}
        </x-admin.notice>
    @endif

    @if ($totals['unsplitOrders'] > 0)
        <x-admin.notice :heading="trans_choice('admin-money.notice.unsplit_heading', $totals['unsplitOrders'], ['count' => $totals['unsplitOrders']])">
            {{ __('admin-money.notice.unsplit_body') }}
        </x-admin.notice>
    @endif

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,210px),1fr))] gap-3.5">
        <x-admin.kpi accent :label="$grossLabel" :value="Format::money($totals['gross'])" :note="trans_choice('admin-money.kpi.gross_note', $totals['orders'], ['count' => $totals['orders']])" />
        <x-admin.kpi accent :label="__('admin-money.kpi.partners')" :value="Format::money($totals['partners'])" :note="$partnersNote" />
        <x-admin.kpi accent :label="__('admin-money.kpi.platform')" :value="Format::money($totals['platform'])" :note="$platformNote" />
        <x-admin.kpi accent :label="__('admin-money.kpi.to_release')" :value="Format::money($totals['toRelease'])" :note="$releaseNote" />
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,380px),1fr))] items-start gap-3.5">
        <x-admin.card :heading="__('admin-money.partners.heading')">
            <x-slot:aside>
                <span class="text-[12.5px] text-gray-400">{{ $current->isMonth() ? $month : $current->label() }}</span>
            </x-slot:aside>

            @if ($partners->isEmpty())
                <x-admin.empty>{{ __('admin-money.partners.empty') }}</x-admin.empty>
            @else
                <div class="overflow-x-auto">
                    <flux:table class="min-w-[max(100%,460px)]">
                        <flux:table.columns>
                            <flux:table.column class="!pl-5">{{ __('admin-money.partners.col_partner') }}</flux:table.column>
                            <flux:table.column align="end">{{ __('admin-money.partners.col_gross') }}</flux:table.column>
                            <flux:table.column align="end">{{ __('admin-money.partners.col_net') }}</flux:table.column>
                            <flux:table.column align="end" class="!pr-5">{{ __('admin-money.partners.col_fee') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($partners as $row)
                                <flux:table.row :key="'partner-'.$row['partnerId']">
                                    <flux:table.cell class="!pl-5">
                                        <span class="block text-[14.5px] font-semibold text-admin-rail">{{ $row['name'] }}</span>
                                        <span class="mt-[3px] flex flex-wrap items-center gap-1.5 text-xs text-gray-400">
                                            {{ trans_choice('admin-money.partners.orders', $row['orders'], ['count' => $row['orders']]) }}
                                            @if ($row['provisional'])
                                                <x-admin.badge tone="warning">{{ __('admin-money.partners.provisional') }}</x-admin.badge>
                                            @endif
                                        </span>
                                    </flux:table.cell>
                                    <flux:table.cell align="end" class="whitespace-nowrap">{{ Format::money($row['gross']) }}</flux:table.cell>
                                    <flux:table.cell align="end" class="whitespace-nowrap">{{ Format::money($row['net']) }}</flux:table.cell>
                                    <flux:table.cell align="end" class="whitespace-nowrap !pr-5 font-bold !text-admin-rail">{{ Format::money($row['commission']) }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </x-admin.card>

        <x-admin.card :heading="__('admin-money.transfers.heading')">
            <x-slot:aside>
                <a href="{{ $stripe->accounts() }}" target="_blank" rel="noopener" class="text-[13px] font-bold text-admin-teal hover:underline">{{ __('admin-money.transfers.open_stripe') }}</a>
            </x-slot:aside>

            @if ($transfers->isEmpty())
                <x-admin.empty>{{ __('admin-money.transfers.empty') }}</x-admin.empty>
            @else
                <div class="overflow-x-auto">
                    <flux:table class="min-w-[max(100%,440px)]">
                        <flux:table.columns>
                            <flux:table.column class="!pl-5">{{ __('admin-money.transfers.col_partner') }}</flux:table.column>
                            <flux:table.column>{{ __('admin-money.transfers.col_date') }}</flux:table.column>
                            <flux:table.column align="end">{{ __('admin-money.transfers.col_amount') }}</flux:table.column>
                            <flux:table.column class="!pr-5">{{ __('admin-money.transfers.col_status') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($transfers as $transfer)
                                @php
                                    $note = match ($transfer['state']) {
                                        'waiting' => __('admin-money.transfers.reason.'.$transfer['reason']),
                                        'retrying' => __('admin-money.transfers.attempt', ['attempts' => $transfer['attempts'], 'max' => $maxAttempts]).($transfer['error'] ? ' · '.Str::limit($transfer['error'], 80) : ''),
                                        'failed' => $transfer['uncertain'] ? __('admin-money.transfers.uncertain') : Str::limit((string) $transfer['error'], 80),
                                        default => null,
                                    };
                                @endphp
                                <flux:table.row :key="$transfer['key']">
                                    <flux:table.cell class="!pl-5">
                                        <span class="flex items-center gap-1.5 font-semibold text-admin-rail">
                                            {{ $transfer['partner'] }}
                                            @if ($transfer['stripeAccount'])
                                                <a href="{{ $stripe->account($transfer['stripeAccount']) }}" target="_blank" rel="noopener" title="{{ __('admin-money.transfers.open_account') }}" aria-label="{{ __('admin-money.transfers.open_account') }}" class="text-admin-teal hover:text-admin-rail">
                                                    <flux:icon.arrow-top-right-on-square class="size-3.5" />
                                                </a>
                                            @endif
                                        </span>
                                    </flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap">{{ Period::formatDay($transfer['date']) }}</flux:table.cell>
                                    <flux:table.cell align="end" class="whitespace-nowrap !text-admin-rail">{{ Format::money($transfer['amount']) }}</flux:table.cell>
                                    <flux:table.cell class="!pr-5">
                                        <x-admin.badge :tone="$stateTones[$transfer['state']]">{{ __('admin-money.transfers.state.'.$transfer['state']) }}</x-admin.badge>
                                        @if (filled($note))
                                            <span class="mt-1 block max-w-[260px] text-xs whitespace-normal text-gray-400">{{ $note }}</span>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </x-admin.card>
    </div>
</div>
