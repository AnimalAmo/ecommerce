{{-- Editor di una pagina legale o libera (design: is_pagina). --}}
<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.pages.index')">{{ __('admin-content.pages.back') }}</x-admin.back-link>

    <div class="flex flex-wrap items-start justify-between gap-3.5">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="m-0 text-[25px] leading-tight font-bold text-admin-rail">{{ $heading }}</h1>
                <x-admin.badge :tone="$legal ? 'muted' : 'purple'">{{ $kindLabel }}</x-admin.badge>
            </div>
            <p class="mt-2 text-[15px] text-gray-600">{{ $path ? $path.' · ' : '' }}{{ $updated }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            @if ($publicUrl)
                <x-admin.button tone="ghost" icon="arrow-top-right-on-square" :href="$publicUrl" target="_blank">{{ __('admin-content.common.view_on_site') }}</x-admin.button>
            @endif
            <x-admin.button icon="eye" wire:click="preview">{{ __('admin-content.common.preview') }}</x-admin.button>
            <x-admin.button tone="primary" wire:click="save">{{ __('admin-content.common.save_publish') }}</x-admin.button>
        </div>
    </div>

    <div class="grid items-start gap-3.5 lg:grid-cols-3">
        <x-admin.card class="lg:col-span-2">
            <x-admin.tabs class="px-5" model="locale" :current="$locale" :items="[
                ['key' => 'it', 'label' => __('admin-content.common.lang_it')],
                ['key' => 'en', 'label' => __('admin-content.common.lang_en')],
            ]" />

            <form wire:submit="save" class="p-5">
                {{-- Entrambe le lingue restano nel DOM (una nascosta): l'editor non si
                     reinizializza al cambio scheda e non perde quello che non è salvato. --}}
                @foreach (['it', 'en'] as $lang)
                    <div wire:key="page-fields-{{ $lang }}" @class(['flex flex-col gap-4', 'hidden' => $locale !== $lang])>
                        @if ($lang === 'en')
                            <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __('admin-content.pages.english_fallback') }}</p>
                        @endif

                        <flux:input wire:model="title.{{ $lang }}" :label="__('admin-content.common.title')" />

                        <flux:editor
                            wire:model="body.{{ $lang }}"
                            :label="__('admin-content.common.text')"
                            toolbar="heading | bold italic underline strike | bullet ordered blockquote | link ~ undo redo"
                            :placeholder="__('admin-content.pages.body_placeholder_'.$lang)"
                            class="**:data-[slot=content]:min-h-[320px]"
                        />
                    </div>
                @endforeach
            </form>
        </x-admin.card>

        <div class="flex min-w-0 flex-col gap-3.5">
            <x-admin.card :heading="__('admin-content.common.settings')">
                <div class="flex flex-col gap-4 p-5 text-[14px]">
                    <div>
                        <p class="m-0 text-[13.5px] font-bold text-admin-rail">{{ __('admin-content.common.page_type') }}</p>
                        <p class="mt-1.5 text-gray-600">{{ $legal ? __('admin-content.pages.type_legal') : __('admin-content.pages.type_free') }}</p>
                        <p class="mt-1 text-[12.5px] leading-normal text-gray-400">{{ __('admin-content.pages.type_locked') }}</p>
                    </div>

                    @if ($legal)
                        <div>
                            <p class="m-0 text-[13.5px] font-bold text-admin-rail">{{ __('admin-content.common.address') }}</p>
                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="mt-1.5 inline-block text-admin-teal hover:underline">{{ $path }}</a>
                            <p class="mt-1 text-[12.5px] leading-normal text-gray-400">{{ __('admin-content.pages.slug_fixed') }}</p>
                        </div>

                        <flux:input
                            type="date"
                            wire:model="lastUpdatedAt"
                            :label="__('admin-content.pages.revision_date')"
                            :description:trailing="__('admin-content.pages.revision_hint')"
                        />
                    @else
                        <flux:input
                            wire:model.blur="slug"
                            :label="__('admin-content.common.address')"
                            :description:trailing="__('admin-content.pages.slug_prefix', ['url' => $slugUrl]).' '.__('admin-content.pages.slug_hint')"
                        />

                        <flux:select wire:model="footerColumn" :label="__('admin-content.pages.footer')">
                            <flux:select.option value="">{{ __('admin-content.pages.footer_none') }}</flux:select.option>
                            @foreach ($footerColumns as $column)
                                <flux:select.option value="{{ $column }}">{{ __('admin-content.pages.footer_column', ['column' => __('admin-content.pages.footer_columns.'.$column)]) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>
            </x-admin.card>

            @if ($page && ! $legal)
                <flux:modal.trigger name="page-delete">
                    <x-admin.button tone="danger" icon="trash" class="self-start">{{ __('admin-content.pages.delete') }}</x-admin.button>
                </flux:modal.trigger>
            @endif
        </div>
    </div>

    <flux:modal name="page-preview" class="w-full max-w-[860px]" wire:close="$set('previewing', false)">
        <div class="flex flex-col">
            <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-content.common.preview') }} · {{ $locale === 'it' ? __('admin-content.common.lang_it') : __('admin-content.common.lang_en') }}</flux:heading>

            @if ($preview)
                @if ($preview['fallback'])
                    <p class="mt-3 text-[13px] text-gray-600">{{ __('admin-content.pages.preview_fallback') }}</p>
                @endif

                @if ($preview['html'] === '')
                    <x-admin.empty>{{ __('admin-content.pages.preview_empty') }}</x-admin.empty>
                @else
                    <div class="mt-5 max-h-[65vh] overflow-y-auto rounded-lg border border-gray-150 bg-white px-6 py-5">
                        <h2 class="text-3xl font-bold text-black">{{ $preview['title'] }}</h2>
                        {{-- HTML già filtrato da HtmlSanitizer, come sul sito. --}}
                        <div class="legal-content mt-6">{!! $preview['html'] !!}</div>
                    </div>
                @endif
            @endif
        </div>
    </flux:modal>

    @if ($page && ! $legal)
        <flux:modal name="page-delete" class="w-full max-w-[460px]">
            <div class="flex flex-col">
                <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-content.pages.delete_title') }}</flux:heading>
                <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ __('admin-content.pages.delete_body', ['title' => $heading]) }}</p>
                <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                    <flux:modal.close>
                        <x-admin.button>{{ __('admin-content.common.cancel') }}</x-admin.button>
                    </flux:modal.close>
                    <x-admin.button tone="danger" wire:click="delete">{{ __('admin-content.common.delete') }}</x-admin.button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
