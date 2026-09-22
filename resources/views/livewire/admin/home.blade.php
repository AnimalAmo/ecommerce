@use('App\Support\Format')
@use('Illuminate\Support\Number')

@php
    $tiles = array_values(array_filter([
        $todo['showApprovals'] ? [
            'count' => $todo['approvals'],
            'label' => trans_choice('admin-dashboard.home.todo.approvals', $todo['approvals']),
            'hint' => $todo['oldestApprovalDays'] === null
                ? __('admin-dashboard.home.todo.approvals_none')
                : trans_choice('admin-dashboard.home.todo.approvals_oldest', $todo['oldestApprovalDays'], ['count' => $todo['oldestApprovalDays']]),
            'url' => route('admin.approvals'),
        ] : null,
        [
            'count' => $todo['applications'],
            'label' => trans_choice('admin-dashboard.home.todo.applications', $todo['applications']),
            'hint' => __('admin-dashboard.home.todo.applications_hint'),
            'url' => route('admin.inbox', ['tab' => 'applications']),
        ],
        [
            'count' => $todo['messages'],
            'label' => trans_choice('admin-dashboard.home.todo.messages', $todo['messages']),
            'hint' => __('admin-dashboard.home.todo.messages_hint'),
            'url' => route('admin.inbox'),
        ],
        [
            'count' => $todo['reviews'],
            'label' => trans_choice('admin-dashboard.home.todo.reviews', $todo['reviews']),
            'hint' => __('admin-dashboard.home.todo.reviews_hint'),
            'url' => route('admin.reviews'),
        ],
        [
            'count' => $todo['flaggedPosts'],
            'label' => trans_choice('admin-dashboard.home.todo.posts', $todo['flaggedPosts']),
            'hint' => __('admin-dashboard.home.todo.posts_hint'),
            'url' => route('admin.community'),
        ],
    ]));

    $number = fn (int $value): string => Number::format($value, locale: app()->getLocale());
    $link = 'text-[13px] font-bold text-admin-teal hover:underline';
    $row = 'flex items-center gap-3 border-b border-admin-row px-5 py-[13px] last:border-b-0 hover:bg-gray-100';
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.page-header :heading="$heading" :sub="__('admin-dashboard.home.subtitle', ['date' => $today])" />

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,240px),1fr))] gap-3.5">
        @foreach ($tiles as $tile)
            <a href="{{ $tile['url'] }}" wire:navigate class="flex min-w-0 flex-col items-start gap-1.5 rounded-[10px] border border-l-[3px] border-gray-150 !border-l-brand-cyan bg-white px-5 py-[18px] text-left hover:bg-gray-100">
                <span class="flex items-baseline gap-[9px]">
                    <span class="text-[30px] leading-none font-bold text-admin-rail">{{ $number($tile['count']) }}</span>
                    <span class="text-[14.5px] font-bold text-admin-rail">{{ $tile['label'] }}</span>
                </span>
                <span class="text-[13px] text-gray-400">{{ $tile['hint'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,200px),1fr))] gap-3.5">
        <x-admin.kpi
            :label="__('admin-dashboard.home.kpi.catalog')"
            :value="$number($catalog['published'])"
            :note="trans_choice('admin-dashboard.home.kpi.catalog_note', $catalog['suspended'], ['count' => $number($catalog['suspended'])])"
        />
        <x-admin.kpi
            :label="__('admin-dashboard.home.kpi.users')"
            :value="$number($subscribers['total'])"
            :note="trans_choice('admin-dashboard.home.kpi.users_note', $subscribers['recent'], ['count' => $number($subscribers['recent'])])"
        />
        <x-admin.kpi
            :label="__('admin-dashboard.home.kpi.orders')"
            :value="$number($sales['orders'])"
            :note="__('admin-dashboard.home.kpi.orders_note', ['amount' => Format::money($sales['gross'])])"
        />
        <x-admin.kpi
            :label="__('admin-dashboard.home.kpi.partners')"
            :value="$number($partners['active'])"
            :note="trans_choice('admin-dashboard.home.kpi.partners_note', $partners['withoutListings'], ['count' => $number($partners['withoutListings'])])"
        />
    </div>

    {{-- "Ordini del mese" conta solo gli incassi: le prenotazioni offline si dicono a parte --}}
    @if ($onSite['count'] > 0)
        <p class="m-0 -mt-2 text-[13px] text-gray-400">{{ trans_choice('admin-dashboard.on_site_note', $onSite['count'], ['count' => $number($onSite['count']), 'amount' => Format::money($onSite['value_cents'])]) }}</p>
    @endif

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,360px),1fr))] items-start gap-3.5">
        <x-admin.card :heading="__('admin-dashboard.home.latest.heading')" class="overflow-hidden">
            <x-slot:aside>
                <a href="{{ route('admin.catalog.index') }}" wire:navigate class="{{ $link }}">{{ __('admin-dashboard.home.latest.link') }}</a>
            </x-slot:aside>

            @forelse ($latest as $item)
                <a href="{{ $item['url'] }}" wire:navigate wire:key="latest-{{ $item['key'] }}" class="{{ $row }}">
                    <span aria-hidden="true" class="h-[38px] w-12 shrink-0 rounded-md bg-brand-cyan-bg bg-cover bg-center" @if ($item['img']) style="background-image: url('{{ $item['img'] }}')" @endif></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[14.5px] font-bold text-admin-rail">{{ $item['name'] }}</span>
                        <span class="mt-[3px] block truncate text-[12.5px] text-gray-400">{{ __('admin-dashboard.home.latest.meta', ['partner' => $item['partner'], 'when' => $item['when']]) }}</span>
                    </span>
                    <x-admin.badge :tone="$item['statusTone']">{{ $item['statusLabel'] }}</x-admin.badge>
                </a>
            @empty
                <x-admin.empty>{{ __('admin-dashboard.home.latest.empty') }}</x-admin.empty>
            @endforelse
        </x-admin.card>

        <x-admin.card :heading="__('admin-dashboard.home.inbox.heading')" class="overflow-hidden">
            <x-slot:aside>
                <a href="{{ route('admin.inbox') }}" wire:navigate class="{{ $link }}">{{ __('admin-dashboard.home.inbox.link') }}</a>
            </x-slot:aside>

            @forelse ($inbox as $entry)
                <a href="{{ $entry['url'] }}" wire:navigate wire:key="inbox-{{ $entry['kind'] }}-{{ $entry['id'] }}" class="{{ $row }}">
                    <x-admin.badge :tone="$entry['kind'] === 'application' ? 'purple' : 'info'">
                        {{ __($entry['kind'] === 'application' ? 'admin-dashboard.home.inbox.kind_application' : 'admin-dashboard.home.inbox.kind_message') }}
                    </x-admin.badge>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[14.5px] font-bold text-admin-rail">{{ $entry['who'] }}</span>
                        <span class="mt-[3px] block truncate text-[12.5px] text-gray-600">{{ $entry['what'] }}</span>
                    </span>
                    <span class="shrink-0 text-xs text-gray-400">{{ $entry['at']->locale(app()->getLocale())->diffForHumans() }}</span>
                </a>
            @empty
                <x-admin.empty>{{ __('admin-dashboard.home.inbox.empty') }}</x-admin.empty>
            @endforelse
        </x-admin.card>
    </div>
</div>
