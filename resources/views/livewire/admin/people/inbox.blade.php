@php
    use App\Models\Partner\PartnerApplication;
    use Illuminate\Support\Str;

    $initials = fn ($i) => mb_strtoupper(mb_substr((string) $i->first_name, 0, 1).mb_substr((string) $i->last_name, 0, 1));
    $listTitle = trans_choice('admin-people.inbox.list_'.$tab.'.'.$filter, $items->total(), ['count' => $items->total()]);
    $fieldRow = 'flex flex-wrap gap-x-4 gap-y-1 border-b border-admin-row py-[9px]';
    $fieldLabel = 'flex-[0_0_140px] text-[13px] text-gray-400';
    $fieldValue = 'min-w-0 flex-1 text-sm font-semibold break-words text-admin-rail';
    $appTones = [PartnerApplication::STATUS_PENDING => 'purple', PartnerApplication::STATUS_INVITED => 'info', PartnerApplication::STATUS_REGISTERED => 'success'];
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.page-header :heading="__('admin-people.inbox.title')" :sub="__('admin-people.inbox.subtitle')" />

    <x-admin.tabs
        model="tab"
        :current="$tab"
        :items="[
            ['key' => 'messages', 'label' => __('admin-people.inbox.tab_messages'), 'count' => $counts['messages']],
            ['key' => 'applications', 'label' => __('admin-people.inbox.tab_applications'), 'count' => $counts['applications']],
        ]"
    />

    <div class="grid items-start gap-3.5 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        <x-admin.card class="overflow-hidden">
            <header class="flex items-center justify-between gap-3 border-b border-gray-150 px-5 py-4">
                <h2 class="m-0 text-base font-bold text-admin-rail">{{ $listTitle }}</h2>
                <flux:select wire:model.live="filter" size="sm" :aria-label="__('admin-people.inbox.show')" class="!w-auto !appearance-auto">
                    <flux:select.option value="open">{{ __('admin-people.inbox.filter_open') }}</flux:select.option>
                    <flux:select.option value="all">{{ __('admin-people.inbox.filter_all') }}</flux:select.option>
                    <flux:select.option value="archived">{{ __('admin-people.inbox.filter_archived') }}</flux:select.option>
                </flux:select>
            </header>

            @forelse ($items as $item)
                @php $active = $current?->id === $item->id; @endphp
                <a
                    href="{{ route('admin.inbox', array_filter(['tab' => $tab !== 'messages' ? $tab : null, 'filter' => $filter !== 'open' ? $filter : null, 'id' => $item->id])) }}"
                    wire:key="inbox-{{ $tab }}-{{ $item->id }}"
                    wire:click.prevent="select({{ $item->id }})"
                    @if ($active) aria-current="true" @endif
                    @class([
                        'flex items-center gap-3 border-b border-l-[3px] border-b-admin-row px-5 py-[13px] text-left',
                        'border-l-brand-cyan bg-[#F7FCFE]' => $active,
                        'border-l-transparent hover:bg-gray-100' => ! $active,
                    ])
                >
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-admin-row text-[12.5px] font-bold text-gray-600">{{ $initials($item) }}</span>
                    <span class="flex min-w-0 flex-1 flex-col gap-[3px]">
                        <span class="flex min-w-0 items-center gap-2">
                            <span class="truncate text-[14.5px] font-bold text-admin-rail">{{ trim($item->first_name.' '.$item->last_name) }}</span>
                            @if ($item->handled_at === null && $item->archived_at === null)
                                <span class="size-[7px] shrink-0 rounded-full bg-brand-magenta" role="img" aria-label="{{ __('admin-people.inbox.unhandled') }}"></span>
                            @endif
                        </span>
                        <span class="truncate text-[12.5px] text-gray-600">
                            {{ $isApplications ? $item->business_name.' — '.$item->offer_type : Str::limit($item->message, 60) }}
                        </span>
                    </span>
                    <span class="shrink-0 text-xs text-gray-400">{{ $item->created_at?->locale('it')->diffForHumans() }}</span>
                </a>
            @empty
                <x-admin.empty>{{ __($filter === 'open' ? 'admin-people.inbox.empty_open' : 'admin-people.inbox.empty') }}</x-admin.empty>
            @endforelse

            @if ($items->hasPages())
                <div class="px-5 py-3">
                    <flux:pagination :paginator="$items" />
                </div>
            @endif
        </x-admin.card>

        <x-admin.card class="min-w-0">
            @if ($current === null)
                <x-admin.empty>{{ __('admin-people.inbox.pick') }}</x-admin.empty>
            @else
                @php
                    $who = trim($current->first_name.' '.$current->last_name);
                    $subject = $isApplications
                        ? __('admin-people.inbox.reply_subject_application')
                        : __('admin-people.inbox.reply_subject_message', ['reason' => $current->reason]);
                    $received = $current->created_at?->locale('it');
                @endphp
                <header class="flex items-start justify-between gap-3 border-b border-gray-150 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="m-0 text-base font-bold text-admin-rail">{{ $who }}</h2>
                        <p class="mt-[5px] text-[12.5px] text-gray-400">
                            {{ $isApplications
                                ? __('admin-people.inbox.arrived', ['city' => $current->city, 'when' => $received?->diffForHumans()])
                                : $current->email.' · '.$received?->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                        @if ($isApplications)
                            @php $status = $current->status ?? PartnerApplication::STATUS_PENDING; @endphp
                            <x-admin.badge :tone="$appTones[$status] ?? 'purple'">{{ __('admin-people.application_status.'.$status) }}</x-admin.badge>
                        @endif
                        @if ($current->archived_at !== null)
                            <x-admin.badge tone="muted">{{ __('admin-people.inbox.archived') }}</x-admin.badge>
                        @elseif ($current->handled_at !== null)
                            <x-admin.badge tone="success">{{ __('admin-people.inbox.handled') }}</x-admin.badge>
                        @else
                            <x-admin.badge tone="warning">{{ __('admin-people.inbox.unhandled') }}</x-admin.badge>
                        @endif
                    </div>
                </header>

                <div class="p-5">
                    @if ($isApplications)
                        <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.business') }}</span><span class="{{ $fieldValue }}">{{ $current->business_name }}</span></div>
                        <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.offer_type') }}</span><span class="{{ $fieldValue }}">{{ $current->offer_type }}</span></div>
                        <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.role') }}</span><span class="{{ $fieldValue }}">{{ $current->role }}</span></div>
                        <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.city') }}</span><span class="{{ $fieldValue }}">{{ $current->city }}</span></div>
                        <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.email') }}</span><span class="{{ $fieldValue }}">{{ $current->email }}</span></div>
                        <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.phone') }}</span><span class="{{ $fieldValue }}">{{ $current->phone }}</span></div>
                        @if (filled($current->website))
                            <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.website') }}</span><span class="{{ $fieldValue }}">{{ $current->website }}</span></div>
                        @endif
                        @if ($current->user_id !== null)
                            <div class="{{ $fieldRow }}">
                                <span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.account') }}</span>
                                <a href="{{ route('admin.users.show', $current->user_id) }}" wire:navigate class="{{ $fieldValue }} !text-admin-teal hover:underline">{{ __('admin-people.inbox.open_account') }}</a>
                            </div>
                        @endif
                        @if ($current->invited_at !== null)
                            <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.last_invite') }}</span><span class="{{ $fieldValue }}">{{ $current->invited_at->locale('it')->isoFormat('D MMM YYYY, HH:mm') }}</span></div>
                        @endif
                    @else
                        <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.email') }}</span><span class="{{ $fieldValue }}">{{ $current->email }}</span></div>
                        <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.subject') }}</span><span class="{{ $fieldValue }}">{{ $current->reason }}</span></div>
                        <div class="{{ $fieldRow }}"><span class="{{ $fieldLabel }}">{{ __('admin-people.inbox.received') }}</span><span class="{{ $fieldValue }}">{{ $received?->isoFormat('D MMM YYYY, HH:mm') }}</span></div>
                    @endif

                    <p class="mt-[18px] text-[14.5px] leading-relaxed whitespace-pre-line text-[#1E2E33]">{{ $isApplications ? $current->description : $current->message }}</p>

                    <div class="mt-6 flex flex-wrap gap-2.5">
                        <x-admin.button tone="primary" icon="envelope" href="mailto:{{ $current->email }}?subject={{ rawurlencode($subject) }}">{{ __('admin-people.inbox.reply') }}</x-admin.button>
                        @if ($isApplications && $current->status !== PartnerApplication::STATUS_REGISTERED)
                            <x-admin.button tone="outline" icon="paper-airplane" wire:click="invite">
                                {{ __($current->status === PartnerApplication::STATUS_INVITED ? 'admin-people.inbox.invite_again' : 'admin-people.inbox.invite') }}
                            </x-admin.button>
                        @endif
                        <x-admin.button tone="outline" :icon="$current->handled_at === null ? 'check' : 'arrow-uturn-left'" wire:click="toggleHandled">
                            {{ __($current->handled_at === null ? 'admin-people.inbox.mark_handled' : 'admin-people.inbox.reopen') }}
                        </x-admin.button>
                        <x-admin.button tone="ghost" icon="archive-box" wire:click="toggleArchived">
                            {{ __($current->archived_at === null ? 'admin-people.inbox.archive' : 'admin-people.inbox.unarchive') }}
                        </x-admin.button>
                        <x-admin.button tone="danger" icon="trash" wire:click="askDelete">{{ __('admin-people.inbox.delete') }}</x-admin.button>
                    </div>
                </div>
            @endif
        </x-admin.card>
    </div>

    <flux:modal name="inbox-delete" class="w-full max-w-[460px]">
        @if ($current !== null)
            <h2 class="m-0 text-[19px] font-bold text-admin-rail">{{ __($isApplications ? 'admin-people.inbox.delete_application_title' : 'admin-people.inbox.delete_message_title') }}</h2>
            <p class="mt-3 text-[14.5px] leading-normal text-gray-600">
                {{ __($isApplications ? 'admin-people.inbox.delete_application_body' : 'admin-people.inbox.delete_message_body', ['name' => trim($current->first_name.' '.$current->last_name)]) }}
            </p>
            <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                <flux:modal.close>
                    <x-admin.button tone="outline">{{ __('admin-people.inbox.cancel') }}</x-admin.button>
                </flux:modal.close>
                <x-admin.button tone="danger" wire:click="delete">{{ __('admin-people.inbox.delete') }}</x-admin.button>
            </div>
        @endif
    </flux:modal>
</div>
