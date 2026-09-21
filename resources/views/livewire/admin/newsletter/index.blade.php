@use('App\Services\Admin\Newsletter\NewsletterAdmin')
@php
    $sub = trans_choice('admin-newsletter.index.confirmed_count', $totals['confirmed'], ['count' => $totals['confirmed']]).' '
        .($lastSent?->started_at
            ? __('admin-newsletter.index.last_sent', ['date' => $lastSent->started_at->translatedFormat('j F')])
            : __('admin-newsletter.index.never_sent'));
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.page-header :heading="__('admin-newsletter.title')" :sub="$sub">
        <x-slot:actions>
            <x-admin.button tone="primary" icon="plus" :href="route('admin.newsletter.create')" wire:navigate>{{ __('admin-newsletter.index.write') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($legacyCount > 0)
        <x-admin.notice tone="warning" :heading="trans_choice('admin-newsletter.legacy.heading', $legacyCount, ['count' => $legacyCount])">
            {{ __('admin-newsletter.legacy.text') }}
            <x-slot:actions>
                <flux:modal.trigger name="newsletter-legacy">
                    <x-admin.button>{{ __('admin-newsletter.legacy.button') }}</x-admin.button>
                </flux:modal.trigger>
            </x-slot:actions>
        </x-admin.notice>
    @endif

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,200px),1fr))] gap-3.5">
        <x-admin.kpi
            :label="__('admin-newsletter.kpi.confirmed')"
            :value="number_format($totals['confirmed'], 0, ',', '.')"
            :note="__('admin-newsletter.kpi.confirmed_note', ['count' => $totals['confirmedThisMonth']])"
        />
        <x-admin.kpi
            :label="__('admin-newsletter.kpi.pending')"
            :value="number_format($totals['pending'], 0, ',', '.')"
            :note="$totals['pendingLegacy'] > 0
                ? __('admin-newsletter.kpi.pending_note_legacy', ['count' => $totals['pendingLegacy']])
                : __('admin-newsletter.kpi.pending_note')"
        />
        <x-admin.kpi
            :label="__('admin-newsletter.kpi.opens')"
            :value="$lastSent?->openRate() !== null ? $lastSent->openRate().'%' : '—'"
            :note="match (true) {
                $lastSent === null => __('admin-newsletter.kpi.opens_none'),
                $lastSent->delivered_count > 0 => __('admin-newsletter.kpi.opens_note', ['opened' => $lastSent->opened_count, 'base' => $lastSent->delivered_count]),
                default => __('admin-newsletter.kpi.opens_note_sent', ['opened' => $lastSent->opened_count, 'base' => $lastSent->sent_count]),
            }"
        />
        <x-admin.kpi
            :label="__('admin-newsletter.kpi.unsubscribed')"
            :value="number_format($totals['unsubscribed90'], 0, ',', '.')"
            :note="__('admin-newsletter.kpi.unsubscribed_note')"
        />
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,380px),1fr))] items-start gap-3.5">
        {{-- Iscritti --}}
        <x-admin.card :heading="__('admin-newsletter.subscribers.heading')">
            <x-slot:aside>
                <a href="{{ $exportUrl }}" class="text-[13.5px] font-bold text-admin-teal hover:underline">{{ __('admin-newsletter.subscribers.export') }}</a>
            </x-slot:aside>

            <x-admin.filters>
                <div class="min-w-[170px] flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('admin-newsletter.subscribers.search')" :aria-label="__('admin-newsletter.subscribers.search')" />
                </div>
                <div class="min-w-[140px]">
                    <flux:select wire:model.live="status" :aria-label="__('admin-newsletter.subscribers.filter_status')">
                        <flux:select.option value="">{{ __('admin-newsletter.subscribers.all_statuses') }}</flux:select.option>
                        @foreach (['confirmed', 'pending', 'unsubscribed', NewsletterAdmin::FILTER_SUPPRESSED] as $value)
                            <flux:select.option value="{{ $value }}">{{ __('admin-newsletter.status_filter.'.$value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="min-w-[140px]">
                    <flux:select wire:model.live="source" :aria-label="__('admin-newsletter.subscribers.filter_source')">
                        <flux:select.option value="">{{ __('admin-newsletter.subscribers.all_sources') }}</flux:select.option>
                        @foreach (['footer', 'registration', 'legacy'] as $value)
                            <flux:select.option value="{{ $value }}">{{ __('admin-newsletter.source.'.$value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="min-w-[120px]">
                    <flux:select wire:model.live="locale" :aria-label="__('admin-newsletter.subscribers.filter_locale')">
                        <flux:select.option value="">{{ __('admin-newsletter.subscribers.all_locales') }}</flux:select.option>
                        <flux:select.option value="it">{{ __('admin-newsletter.locale.it') }}</flux:select.option>
                        <flux:select.option value="en">{{ __('admin-newsletter.locale.en') }}</flux:select.option>
                    </flux:select>
                </div>
            </x-admin.filters>

            @if ($page->isEmpty())
                <x-admin.empty>{{ $hasFilters ? __('admin-newsletter.subscribers.empty_filtered') : __('admin-newsletter.subscribers.empty') }}</x-admin.empty>
            @else
                <div class="overflow-x-auto">
                    <flux:table class="min-w-[420px]">
                        <flux:table.columns>
                            <flux:table.column class="!pl-5">{{ __('admin-newsletter.subscribers.col_address') }}</flux:table.column>
                            <flux:table.column>{{ __('admin-newsletter.subscribers.col_consent') }}</flux:table.column>
                            <flux:table.column align="end" class="!pr-5">{{ __('admin-newsletter.subscribers.col_actions') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($page as $subscriber)
                                <flux:table.row :key="'subscriber-'.$subscriber->id">
                                    <flux:table.cell class="!pl-5">
                                        <p class="m-0 text-[14.5px] font-semibold break-all text-admin-rail">{{ $subscriber->email }}</p>
                                        <p class="mt-[3px] text-xs text-gray-400">
                                            {{ __('admin-newsletter.source.'.$subscriber->source) }}@if ($subscriber->locale === 'en') · {{ __('admin-newsletter.locale.en') }}@endif
                                        </p>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <x-admin.badge :tone="NewsletterAdmin::statusTone($subscriber->status)">{{ __('admin-newsletter.status.'.$subscriber->status) }}</x-admin.badge>
                                        <p class="mt-[5px] text-xs text-gray-400">{{ $admin->proofLine($subscriber) }}</p>
                                    </flux:table.cell>
                                    <flux:table.cell class="!pr-5">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-admin.icon-action tone="view" icon="eye" :label="__('admin-newsletter.subscribers.view_proof')" wire:click="showProof({{ $subscriber->id }})" />
                                            @if (in_array($subscriber->status, ['confirmed', 'pending'], true))
                                                <x-admin.icon-action tone="delete" icon="trash" :label="__('admin-newsletter.subscribers.unsubscribe')" wire:click="askUnsubscribe({{ $subscriber->id }})" />
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2.5 px-5 py-3.5">
                    <span class="text-[13.5px] text-gray-400">{{ __('admin-newsletter.subscribers.shown', ['shown' => $page->count(), 'total' => $page->total()]) }}</span>
                    <div>{{ $page->onEachSide(1)->links() }}</div>
                </div>
            @endif
        </x-admin.card>

        {{-- Invii --}}
        <x-admin.card :heading="__('admin-newsletter.campaigns.heading')">
            @if ($campaigns->isEmpty())
                <x-admin.empty>{{ __('admin-newsletter.campaigns.empty') }}</x-admin.empty>
            @else
                <div class="overflow-x-auto">
                    <flux:table class="min-w-[420px]">
                        <flux:table.columns>
                            <flux:table.column class="!pl-5">{{ __('admin-newsletter.campaigns.col_subject') }}</flux:table.column>
                            <flux:table.column>{{ __('admin-newsletter.campaigns.col_sent') }}</flux:table.column>
                            <flux:table.column align="end">{{ __('admin-newsletter.campaigns.col_opens') }}</flux:table.column>
                            <flux:table.column class="!pr-5">{{ __('admin-newsletter.campaigns.col_status') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($campaigns as $campaign)
                                <flux:table.row :key="'campaign-'.$campaign->id">
                                    <flux:table.cell class="!pl-5">
                                        <a href="{{ route('admin.newsletter.edit', $campaign) }}" wire:navigate class="font-semibold text-admin-rail hover:text-admin-teal">
                                            {{ $campaign->getTranslation('subject', 'it', false) ?: __('admin-newsletter.campaigns.no_subject') }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap">{{ $campaign->started_at?->translatedFormat('j M Y') ?? '—' }}</flux:table.cell>
                                    <flux:table.cell align="end">
                                        @if ($campaign->status === 'sending')
                                            {{ __('admin-newsletter.campaigns.progress', ['sent' => $campaign->sent_count, 'total' => $campaign->recipients_count]) }}
                                        @else
                                            {{ $campaign->openRate() !== null ? $campaign->openRate().'%' : '—' }}
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell class="!pr-5">
                                        <x-admin.badge :tone="NewsletterAdmin::campaignTone($campaign->status)">{{ __('admin-newsletter.campaign_status.'.$campaign->status) }}</x-admin.badge>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </x-admin.card>
    </div>

    {{-- Prova del consenso --}}
    <flux:modal name="newsletter-proof" class="w-full max-w-[520px]" wire:close="closeModal">
        @if ($selected)
            @php
                $at = fn ($date) => $date?->translatedFormat('j M Y, H:i:s') ?? '—';
                $rows = [
                    'email' => $selected->email,
                    'status' => __('admin-newsletter.status.'.$selected->status),
                    'source' => __('admin-newsletter.source.'.$selected->source),
                    'locale' => __('admin-newsletter.locale.'.$selected->locale),
                    'account' => $selected->user ? __('admin-newsletter.proof.account_yes', ['name' => $selected->user->name]) : '—',
                    'requested' => $at($selected->requested_at),
                    'consent_text' => $selected->consent_text ?? '—',
                    'request_ip' => $selected->consent_ip ?? '—',
                    'request_ua' => $selected->consent_user_agent ?? '—',
                    'confirmation_sent' => $at($selected->confirmation_sent_at),
                    'confirmed' => $at($selected->confirmed_at),
                    'confirm_ip' => $selected->confirmation_ip ?? '—',
                    'confirm_ua' => $selected->confirmation_user_agent ?? '—',
                    'unsubscribed' => $at($selected->unsubscribed_at),
                    'suppressed' => $at($selected->suppressed_at),
                ];
            @endphp
            <div class="flex flex-col">
                <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-newsletter.proof.title') }}</flux:heading>
                @if ($selected->legacy)
                    <p class="mt-3 text-[13px] leading-normal text-gray-600">{{ __('admin-newsletter.proof.legacy_note') }}</p>
                @endif

                <dl class="mt-4 divide-y divide-admin-row text-[13.5px]">
                    @foreach ($rows as $key => $value)
                        <div class="grid grid-cols-[150px_1fr] gap-3 py-2 max-sm:grid-cols-1 max-sm:gap-0.5">
                            <dt class="font-semibold text-gray-600">{{ __('admin-newsletter.proof.'.$key) }}</dt>
                            <dd class="m-0 break-words text-admin-rail">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-6 flex justify-end">
                    <flux:modal.close>
                        <x-admin.button>{{ __('admin-newsletter.common.close') }}</x-admin.button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Disiscrizione dal pannello --}}
    <flux:modal name="newsletter-unsubscribe" class="w-full max-w-[460px]" wire:close="closeModal">
        @if ($selected)
            <div class="flex flex-col">
                <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-newsletter.unsubscribe_modal.title') }}</flux:heading>
                <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ __('admin-newsletter.unsubscribe_modal.body', ['email' => $selected->email]) }}</p>

                <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                    <flux:modal.close>
                        <x-admin.button>{{ __('admin-newsletter.common.cancel') }}</x-admin.button>
                    </flux:modal.close>
                    <x-admin.button tone="danger" wire:click="unsubscribe">{{ __('admin-newsletter.unsubscribe_modal.confirm') }}</x-admin.button>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Conferma di cortesia ai contatti della vecchia casella --}}
    <flux:modal name="newsletter-legacy" class="w-full max-w-[460px]">
        <div class="flex flex-col">
            <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-newsletter.legacy.modal_title') }}</flux:heading>
            <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ trans_choice('admin-newsletter.legacy.modal_body', $legacyCount, ['count' => $legacyCount]) }}</p>

            <div class="mt-4 flex gap-2.5 rounded-lg border border-[#FFE0B2] bg-[#FFFBF4] px-[15px] py-[13px]" role="note">
                <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0 text-[#B45A0B]" />
                <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __('admin-newsletter.legacy.modal_note') }}</p>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                <flux:modal.close>
                    <x-admin.button>{{ __('admin-newsletter.common.cancel') }}</x-admin.button>
                </flux:modal.close>
                <x-admin.button tone="primary" wire:click="sendLegacyConfirmations">{{ __('admin-newsletter.legacy.modal_confirm') }}</x-admin.button>
            </div>
        </div>
    </flux:modal>
</div>
