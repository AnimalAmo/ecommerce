@use('App\Services\Admin\Newsletter\NewsletterAdmin')
@php
    $audienceTotal = $counts[$audience] ?? 0;
@endphp

<div class="flex flex-col gap-[18px]" @unless ($readOnly) wire:init="checkDmarc" @endunless>
    <x-admin.back-link :href="route('admin.newsletter.index')">{{ __('admin-newsletter.editor.back') }}</x-admin.back-link>

    @if ($readOnly)
        {{-- Campagna partita: sola lettura, con l'avanzamento. --}}
        <x-admin.page-header
            :heading="$campaign->getTranslation('subject', 'it', false) ?: __('admin-newsletter.campaigns.no_subject')"
            :sub="__('admin-newsletter.report.sub', [
                'status' => __('admin-newsletter.campaign_status.'.$campaign->status),
                'date' => $campaign->started_at?->translatedFormat('j M Y, H:i') ?? '—',
            ])"
        >
            <x-slot:actions>
                <x-admin.button icon="eye" wire:click="openPreview('it')">{{ __('admin-newsletter.editor.preview') }}</x-admin.button>
            </x-slot:actions>
        </x-admin.page-header>

        @if ($stalled)
            <x-admin.notice tone="warning" :heading="__('admin-newsletter.report.stalled_heading')">
                {{ __('admin-newsletter.report.stalled_text', ['time' => $lastActivity?->translatedFormat('j M, H:i') ?? '—']) }}
                <x-slot:actions>
                    <x-admin.button wire:click="resume">{{ __('admin-newsletter.report.resume') }}</x-admin.button>
                </x-slot:actions>
            </x-admin.notice>
        @endif

        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,170px),1fr))] gap-3.5">
            <x-admin.kpi :label="__('admin-newsletter.report.recipients')" :value="number_format($campaign->recipients_count, 0, ',', '.')" :note="__('admin-newsletter.audience.'.$campaign->audience)" />
            <x-admin.kpi :label="__('admin-newsletter.report.sent')" :value="number_format($campaign->sent_count, 0, ',', '.')" :note="$campaign->finished_at ? __('admin-newsletter.report.finished', ['date' => $campaign->finished_at->translatedFormat('j M, H:i')]) : __('admin-newsletter.campaign_status.'.$campaign->status)" />
            <x-admin.kpi :label="__('admin-newsletter.report.delivered')" :value="number_format($campaign->delivered_count, 0, ',', '.')" :note="__('admin-newsletter.report.delivered_note')" />
            <x-admin.kpi :label="__('admin-newsletter.report.opens')" :value="$campaign->openRate() !== null ? $campaign->openRate().'%' : '—'" :note="__('admin-newsletter.report.opens_note', ['count' => $campaign->opened_count])" />
            <x-admin.kpi :label="__('admin-newsletter.report.failed')" :value="number_format($campaign->failed_count, 0, ',', '.')" :note="__('admin-newsletter.report.failed_note')" />
        </div>
    @else
        <x-admin.page-header
            :heading="__('admin-newsletter.editor.title_new')"
            :sub="($campaign?->updated_at
                    ? __('admin-newsletter.editor.saved_at', ['time' => $campaign->updated_at->format('H:i')])
                    : __('admin-newsletter.editor.not_saved'))
                .' · '.trans_choice('admin-newsletter.editor.goes_to', $audienceTotal, ['count' => number_format($audienceTotal, 0, ',', '.')])"
        >
            <x-slot:actions>
                <x-admin.button tone="ghost" icon="eye" wire:click="openPreview('{{ $tab }}')">{{ __('admin-newsletter.editor.preview') }}</x-admin.button>
                <x-admin.button wire:click="save">{{ __('admin-newsletter.editor.save') }}</x-admin.button>
                <x-admin.button icon="envelope" wire:click="openTest">{{ __('admin-newsletter.editor.send_test') }}</x-admin.button>
                <x-admin.button tone="primary" icon="paper-airplane" wire:click="askLaunch">{{ __('admin-newsletter.editor.send_all') }}</x-admin.button>
            </x-slot:actions>
        </x-admin.page-header>

        <div class="grid items-start gap-3.5 lg:grid-cols-3">
            {{-- Testo, una scheda per lingua --}}
            <x-admin.card class="lg:col-span-2">
                <flux:tab.group>
                    <flux:tabs wire:model.live="tab" class="!gap-7 !px-5">
                        <flux:tab name="it">{{ __('admin-newsletter.editor.tab_it') }}</flux:tab>
                        <flux:tab name="en">{{ __('admin-newsletter.editor.tab_en') }}</flux:tab>
                    </flux:tabs>

                    @foreach (['it', 'en'] as $locale)
                        <flux:tab.panel :name="$locale" class="!p-0">
                            <div class="flex flex-col gap-4 p-5">
                                @if ($locale === 'en')
                                    <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __('admin-newsletter.editor.english_hint') }}</p>
                                @endif
                                <flux:input wire:model="subject.{{ $locale }}" :label="__('admin-newsletter.editor.subject')" />
                                <flux:input wire:model="preheader.{{ $locale }}" :label="__('admin-newsletter.editor.preheader')" :description="__('admin-newsletter.editor.preheader_hint')" />
                                <flux:editor
                                    wire:model="body.{{ $locale }}"
                                    :label="__('admin-newsletter.editor.body')"
                                    toolbar="heading | bold italic underline | bullet ordered blockquote | link"
                                    class="**:data-[slot=content]:min-h-[260px]"
                                />
                            </div>
                        </flux:tab.panel>
                    @endforeach
                </flux:tab.group>
            </x-admin.card>

            <div class="flex min-w-0 flex-col gap-3.5">
                <x-admin.card :heading="__('admin-newsletter.editor.recipients')">
                    <div class="flex flex-col gap-4 p-5">
                        <flux:select wire:model.live="audience" :label="__('admin-newsletter.editor.audience')">
                            @foreach ($counts as $value => $count)
                                <flux:select.option value="{{ $value }}">{{ __('admin-newsletter.editor.audience_option.'.$value, ['count' => number_format($count, 0, ',', '.')]) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="hourlyRate" :label="__('admin-newsletter.editor.rate')">
                            @foreach ($rates as $rate)
                                <flux:select.option value="{{ $rate }}">{{ $rate > 0 ? __('admin-newsletter.editor.rate_option', ['rate' => $rate]) : __('admin-newsletter.editor.rate_all') }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <p class="m-0 text-[12.5px] leading-normal text-gray-400">
                            {{ $effectiveRate > 0
                                ? __('admin-newsletter.editor.rate_hint', ['rate' => $effectiveRate, 'duration' => $duration])
                                : __('admin-newsletter.editor.rate_hint_all') }}
                            {{ __('admin-newsletter.editor.bounce_hint') }}
                        </p>
                    </div>
                </x-admin.card>

                <x-admin.card :heading="__('admin-newsletter.editor.checklist')">
                    <ul class="m-0 flex list-none flex-col p-0">
                        @foreach ($checklist as $item)
                            <li class="flex items-start gap-2.5 border-b border-admin-row px-5 py-3 last:border-b-0">
                                <span @class([
                                    'mt-px inline-flex size-[19px] shrink-0 items-center justify-center rounded-full',
                                    'bg-[#E7F7EC] text-[#1B7A3E]' => $item['done'] === true,
                                    'bg-[#FFF8E5] text-[#8A6200]' => $item['done'] === false,
                                    'bg-admin-row text-gray-400' => $item['done'] === null,
                                ])>
                                    @if ($item['done'] === true)
                                        <flux:icon.check class="size-[11px]" />
                                    @elseif ($item['done'] === false)
                                        <flux:icon.exclamation-triangle class="size-[11px]" />
                                    @else
                                        <flux:icon.ellipsis-horizontal class="size-[11px]" />
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1 text-[13.5px] leading-[1.45] text-ink-800">{{ $item['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>
            </div>
        </div>

        {{-- Invio di prova --}}
        <flux:modal name="newsletter-test" class="w-full max-w-[460px]">
            <form wire:submit="sendTest" class="flex flex-col">
                <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-newsletter.editor.test_title') }}</flux:heading>
                <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ __('admin-newsletter.editor.test_text') }}</p>

                <div class="mt-4 flex flex-col gap-4">
                    <flux:input type="email" wire:model="testEmail" :label="__('admin-newsletter.editor.test_email')" />
                    <flux:select wire:model="testLocale" :label="__('admin-newsletter.editor.test_locale')">
                        <flux:select.option value="it">{{ __('admin-newsletter.editor.tab_it') }}</flux:select.option>
                        <flux:select.option value="en">{{ __('admin-newsletter.editor.tab_en') }}</flux:select.option>
                    </flux:select>
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                    <flux:modal.close>
                        <x-admin.button>{{ __('admin-newsletter.common.cancel') }}</x-admin.button>
                    </flux:modal.close>
                    <x-admin.button type="submit" tone="primary">{{ __('admin-newsletter.editor.test_submit') }}</x-admin.button>
                </div>
            </form>
        </flux:modal>

        {{-- Conferma di "Invia a tutti" --}}
        <flux:modal name="newsletter-launch" class="w-full max-w-[460px]">
            <div class="flex flex-col">
                <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ trans_choice('admin-newsletter.editor.launch_title', $audienceTotal, ['count' => number_format($audienceTotal, 0, ',', '.')]) }}</flux:heading>
                <p class="mt-3 text-[14.5px] leading-normal text-gray-600">
                    {{ $effectiveRate > 0
                        ? __('admin-newsletter.editor.launch_body', ['rate' => $effectiveRate, 'duration' => $duration])
                        : __('admin-newsletter.editor.launch_body_all') }}
                </p>

                <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                    <flux:modal.close>
                        <x-admin.button>{{ __('admin-newsletter.common.cancel') }}</x-admin.button>
                    </flux:modal.close>
                    <x-admin.button tone="primary" wire:click="launch">{{ __('admin-newsletter.editor.launch_confirm') }}</x-admin.button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Anteprima: la mail vera, resa come la vedrà l'iscritto. --}}
    <flux:modal name="newsletter-preview" class="w-full max-w-[760px]" wire:close="$set('previewLocale', null)">
        @if ($previewHtml !== null)
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-center justify-between gap-3 pr-8">
                    <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-newsletter.editor.preview_title') }}</flux:heading>
                    <div class="flex gap-2">
                        @foreach (['it', 'en'] as $locale)
                            <x-admin.button
                                :tone="$previewLocale === $locale ? 'primary' : 'outline'"
                                wire:click="openPreview('{{ $locale }}')"
                                class="!h-8 !px-3.5 !text-[13px]"
                            >{{ __('admin-newsletter.editor.tab_'.$locale) }}</x-admin.button>
                        @endforeach
                    </div>
                </div>
                <iframe
                    title="{{ __('admin-newsletter.editor.preview_title') }}"
                    srcdoc="{{ $previewHtml }}"
                    sandbox="allow-popups allow-popups-to-escape-sandbox"
                    class="h-[70vh] w-full rounded-lg border border-gray-150 bg-white"
                ></iframe>
            </div>
        @endif
    </flux:modal>
</div>
