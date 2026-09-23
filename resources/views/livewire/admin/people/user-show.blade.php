@php
    use App\Enums\OrderPaymentMode;
    use App\Enums\OrderStatus;
    use App\Services\Admin\Catalog\AdminServiceCreator;
    use App\Services\Admin\People\UserDirectory;
    use Illuminate\Support\Carbon;

    $th = '!text-[13.5px] !font-semibold !text-gray-400';
    $anonymizedAt = $user->anonymized_at !== null ? Carbon::parse($user->anonymized_at) : null;
    $date = fn ($d) => $d?->locale('it')->isoFormat('D MMM YYYY');
    $row = 'flex flex-wrap gap-x-4 gap-y-1 border-b border-admin-row py-[9px] last:border-b-0';
    $label = 'flex-[0_0_150px] text-[13px] text-gray-400';
    $value = 'min-w-0 flex-1 text-sm font-semibold break-words text-admin-rail';
    $state = UserDirectory::status($user);
    $nlKey = in_array($newsletterState, ['confirmed', 'pending', 'unsubscribed'], true) ? $newsletterState : ($newsletterState === null ? 'none' : 'suppressed');
    $nlTone = ['confirmed' => 'success', 'pending' => 'warning', 'unsubscribed' => 'muted', 'none' => 'muted', 'suppressed' => 'danger'][$nlKey];
    $appTones = ['pending' => 'warning', 'invited' => 'info', 'registered' => 'success'];
    $orderTones = ['paid' => 'success', 'confirmed' => 'info', 'pending' => 'warning', 'cancelled' => 'muted'];
    $paid = UserDirectory::money((int) $orders->where('status', OrderStatus::Paid)->sum('total_cents'));
    // Smartbox: la "finestra" è solo la validità del cofanetto, non una data di soggiorno.
    $when = function ($item): ?string {
        if ($item->purchasable_type === 'smartbox_package' || $item->booked_from === null) {
            return null;
        }

        $from = $item->booked_from->format('d/m/Y');
        $until = $item->booked_until?->format('d/m/Y');

        return $until !== null && $until !== $from ? $from.' – '.$until : $from;
    };
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.users.index')">{{ __('admin-people.users.back') }}</x-admin.back-link>

    <x-admin.page-header :heading="$user->name" :sub="$user->email">
        <x-slot:actions>
            @if ($anonymizedAt === null)
                <x-admin.button tone="outline" :icon="$user->is_active ? 'no-symbol' : 'check'" wire:click="toggleActive">
                    {{ __('admin-people.users.'.($user->is_active ? 'deactivate' : 'reactivate')) }}
                </x-admin.button>
                <x-admin.button tone="danger" icon="trash" wire:click="askAnonymize">{{ __('admin-people.users.anonymize') }}</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($anonymizedAt !== null)
        <x-admin.notice :heading="__('admin-people.users.anonymized_heading')">
            {{ __('admin-people.users.anonymized_body', ['date' => $date($anonymizedAt)]) }}
        </x-admin.notice>
    @endif

    <div class="grid items-start gap-3.5 lg:grid-cols-2">
        <x-admin.card :heading="__('admin-people.users.profile')">
            <div class="px-5 py-3">
                <div class="{{ $row }}"><span class="{{ $label }}">{{ __('admin-people.users.first_name') }}</span><span class="{{ $value }}">{{ $user->first_name }}</span></div>
                <div class="{{ $row }}"><span class="{{ $label }}">{{ __('admin-people.users.last_name') }}</span><span class="{{ $value }}">{{ $user->last_name ?: '—' }}</span></div>
                <div class="{{ $row }}"><span class="{{ $label }}">{{ __('admin-people.users.email') }}</span><span class="{{ $value }}">{{ $user->email }}</span></div>
                <div class="{{ $row }}"><span class="{{ $label }}">{{ __('admin-people.users.phone') }}</span><span class="{{ $value }}">{{ $user->phone ?: '—' }}</span></div>
                <div class="{{ $row }}"><span class="{{ $label }}">{{ __('admin-people.users.birth_date') }}</span><span class="{{ $value }}">{{ $user->birth_date?->format('d/m/Y') ?? '—' }}</span></div>
                <div class="{{ $row }}">
                    <span class="{{ $label }}">{{ __('admin-people.users.address') }}</span>
                    <span class="{{ $value }}">{{ collect([$user->address, trim($user->postal_code.' '.$user->city)])->filter()->implode(', ') ?: '—' }}</span>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card :heading="__('admin-people.users.account')">
            <div class="px-5 py-3">
                <div class="{{ $row }}">
                    <span class="{{ $label }}">{{ __('admin-people.users.role_label') }}</span>
                    <span class="{{ $value }}"><x-admin.badge :tone="$isPartner ? 'purple' : 'muted'">{{ __('admin-people.role.'.($isPartner ? 'partner' : 'client')) }}</x-admin.badge></span>
                </div>
                <div class="{{ $row }}">
                    <span class="{{ $label }}">{{ __('admin-people.users.status_label') }}</span>
                    <span class="{{ $value }}"><x-admin.badge :tone="['active' => 'info', 'inactive' => 'warning', 'anonymized' => 'muted'][$state]">{{ __('admin-people.users.status.'.$state) }}</x-admin.badge></span>
                </div>
                <div class="{{ $row }}"><span class="{{ $label }}">{{ __('admin-people.users.since') }}</span><span class="{{ $value }}">{{ $date($user->created_at) }}</span></div>
                <div class="{{ $row }}">
                    <span class="{{ $label }}">{{ __('admin-people.users.newsletter_label') }}</span>
                    <span class="{{ $value }}"><x-admin.badge :tone="$nlTone">{{ __('admin-people.users.newsletter_state.'.$nlKey) }}</x-admin.badge></span>
                </div>
                <div class="{{ $row }}">
                    <span class="{{ $label }}">{{ __('admin-people.users.marketing') }}</span>
                    <span class="{{ $value }}">{{ __('admin-people.users.'.($user->marketing_consent ? 'yes' : 'no')) }}</span>
                </div>
            </div>
            @if ($anonymizedAt === null)
                <p class="m-0 border-t border-gray-150 px-5 py-3 text-[12.5px] leading-normal text-gray-400">{{ __('admin-people.users.active_hint') }}</p>
            @endif
        </x-admin.card>
    </div>

    @if ($partner !== null)
        <x-admin.card :heading="__('admin-people.users.partner')">
            {{-- Un disattivato non entrerebbe comunque: niente link da rimandare,
                 e nessuna scheda da intestargli (AdminServiceCreator lo rifiuterebbe). --}}
            @if ($user->is_active && $anonymizedAt === null)
                <x-slot:aside>
                    <flux:dropdown position="bottom" align="end">
                        <x-admin.button tone="primary" icon="plus" icon-trailing="chevron-down">{{ __('admin-catalog.create.entry.dropdown') }}</x-admin.button>
                        <flux:menu>
                            @foreach (AdminServiceCreator::CREATABLE_FAMILIES as $family)
                                <flux:menu.item
                                    wire:key="create-{{ $family }}"
                                    href="{{ route('admin.catalog.create', ['family' => $family, 'partner' => $user->id]) }}"
                                    wire:navigate
                                >{{ __('admin-catalog.create.entry.family.'.$family) }}</flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                    <x-admin.button tone="outline" icon="envelope" wire:click="resendWelcome">{{ __('admin-people.users.resend_welcome') }}</x-admin.button>
                </x-slot:aside>
            @endif
            <div class="px-5 py-3">
                <div class="{{ $row }}"><span class="{{ $label }}">{{ __('admin-people.users.business_name') }}</span><span class="{{ $value }}">{{ $partner['business_name'] ?: '—' }}</span></div>
                <div class="{{ $row }} items-center">
                    <span class="{{ $label }}">{{ __('admin-people.users.listings') }}</span>
                    <span class="{{ $value }}">
                        {{ collect([
                            trans_choice('admin-people.users.listings_count', $partner['listings'], ['count' => $partner['listings']]),
                            $partner['suspended'] > 0 ? trans_choice('admin-people.users.listings_suspended', $partner['suspended'], ['count' => $partner['suspended']]) : null,
                        ])->filter()->implode(', ') }}
                    </span>
                    @if ($partner['listings'] > 0)
                        <a href="{{ route('admin.catalog.index', ['partner' => $user->id]) }}" wire:navigate class="text-[13.5px] font-bold text-admin-teal hover:underline">{{ __('admin-people.users.listings_open') }}</a>
                    @endif
                </div>
                <div class="{{ $row }}">
                    <span class="{{ $label }}">{{ __('admin-people.users.bookings_received') }}</span>
                    <span class="{{ $value }}">{{ trans_choice('admin-people.users.bookings_count', $partner['bookings'], ['count' => $partner['bookings']]) }}</span>
                </div>
                <div class="{{ $row }} items-center">
                    <span class="{{ $label }}">{{ __('admin-people.users.payment_mode_label') }}</span>
                    <span class="{{ $value }}">
                        <x-admin.badge :tone="$partner['payment_mode'] === OrderPaymentMode::OnSite->value ? 'warning' : 'info'">{{ __('admin-people.users.payment_mode.'.$partner['payment_mode']) }}</x-admin.badge>
                        {{-- Link già verificato http/https da PartnerPaymentModeService: sicuro come href. --}}
                        @if ($partner['payment_url'] !== null)
                            <a href="{{ $partner['payment_url'] }}" target="_blank" rel="noopener noreferrer" class="ml-2 text-[13.5px] font-normal break-all text-admin-teal hover:underline">{{ $partner['payment_url'] }}</a>
                        @endif
                    </span>
                    @if ($user->partnerProfile !== null)
                        <x-admin.button tone="ghost" icon="pencil-square" wire:click="editPaymentMode">{{ __('admin-people.users.payment_mode_change') }}</x-admin.button>
                    @endif
                </div>
                <div class="{{ $row }}">
                    <span class="{{ $label }}">{{ __('admin-people.users.stripe_label') }}</span>
                    <span class="{{ $value }}">
                        <x-admin.badge :tone="['payable' => 'success', 'incomplete' => 'warning', 'none' => 'muted'][$partner['stripe_status']]">{{ __('admin-people.users.stripe_status.'.$partner['stripe_status']) }}</x-admin.badge>
                    </span>
                </div>
            </div>
        </x-admin.card>
    @endif

    <x-admin.card :heading="__('admin-people.users.orders')">
        <x-slot:aside>
            <span class="text-[13px] text-gray-400">{{ trans_choice('admin-people.users.orders_summary', $orders->count(), ['paid' => $paid]) }}</span>
        </x-slot:aside>
        @if ($orders->isEmpty())
            <x-admin.empty>{{ __('admin-people.users.orders_empty') }}</x-admin.empty>
        @else
            <div class="px-5 pb-2">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column :class="$th">{{ __('admin-people.users.order_number') }}</flux:table.column>
                        <flux:table.column :class="$th">{{ __('admin-people.users.order_date') }}</flux:table.column>
                        <flux:table.column :class="$th">{{ __('admin-people.users.order_items') }}</flux:table.column>
                        <flux:table.column :class="$th" align="end">{{ __('admin-people.users.order_total') }}</flux:table.column>
                        <flux:table.column :class="$th">{{ __('admin-people.users.order_status') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($orders as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell class="!font-semibold !text-admin-rail">{{ $order->order_number }}</flux:table.cell>
                                <flux:table.cell class="!text-gray-600">{{ $date($order->created_at) }}</flux:table.cell>
                                <flux:table.cell class="!whitespace-normal">
                                    @foreach ($order->items as $item)
                                        <span wire:key="order-item-{{ $item->id }}" class="block text-[13.5px] text-admin-rail">
                                            {{ $item->title }}@if ($dates = $when($item))<span class="text-gray-400"> · {{ $dates }}</span>@endif
                                        </span>
                                    @endforeach
                                </flux:table.cell>
                                <flux:table.cell align="end">{{ UserDirectory::money((int) $order->total_cents) }}</flux:table.cell>
                                <flux:table.cell>
                                    {{-- Fallback: uno stato senza tono non deve mai mandare la scheda in errore --}}
                                    <x-admin.badge :tone="$orderTones[$order->status->value] ?? 'muted'">{{ __('admin-people.users.order_statuses.'.$order->status->value) }}</x-admin.badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </x-admin.card>

    <div class="grid items-start gap-3.5 lg:grid-cols-2">
        <x-admin.card :heading="__('admin-people.users.pets')">
            @if ($pets->isEmpty())
                <x-admin.empty>{{ __('admin-people.users.pets_empty') }}</x-admin.empty>
            @else
                <div class="px-5 py-3">
                    @foreach ($pets as $pet)
                        <div wire:key="pet-{{ $pet->id }}" class="{{ $row }}">
                            <span class="{{ $label }}">{{ $pet->species }}</span>
                            <span class="{{ $value }}">{{ collect([$pet->name, $pet->size])->filter()->implode(' · ') ?: '—' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.card>

        <x-admin.card :heading="__('admin-people.users.applications')">
            @if ($applications->isEmpty())
                <x-admin.empty>{{ __('admin-people.users.applications_empty') }}</x-admin.empty>
            @else
                <div class="px-5 py-3">
                    @foreach ($applications as $application)
                        <div wire:key="application-{{ $application->id }}" class="{{ $row }} items-center">
                            <span class="{{ $label }}">{{ $date($application->created_at) }}</span>
                            <span class="{{ $value }}">{{ $application->business_name }} <span class="font-normal text-gray-600">· {{ $application->offer_type }}</span></span>
                            <x-admin.badge :tone="$appTones[$application->status] ?? 'warning'">{{ __('admin-people.application_status.'.($application->status ?? 'pending')) }}</x-admin.badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.card>
    </div>

    @include('livewire.admin.people.partials.anonymize-modal')

    @if ($partner !== null)
        @include('livewire.admin.people.partials.payment-mode-modal')
    @endif
</div>
