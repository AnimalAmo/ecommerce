{{-- Testi di una pagina del sito (design: is_pagina con tipo "Sito"). --}}
@php
    $path = parse_url($publicUrl, PHP_URL_PATH) ?: '/';
    $updated = $status['updated_at']
        ? __('admin-content.common.modified_on', ['date' => $status['updated_at']->locale('it')->translatedFormat('j M Y')])
        : __('admin-content.common.never_modified');
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.pages.index')">{{ __('admin-content.site.back') }}</x-admin.back-link>

    <x-admin.page-header :heading="$label" :sub="$path.' · '.$updated">
        <x-slot:actions>
            <x-admin.button icon="arrow-top-right-on-square" :href="$publicUrl" target="_blank">{{ __('admin-content.common.view_on_site') }}</x-admin.button>
            <x-admin.button tone="primary" wire:click="save">{{ __('admin-content.common.save_publish') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid items-start gap-3.5 lg:grid-cols-3">
        <x-admin.card class="lg:col-span-2">
            <x-admin.tabs class="px-5" model="locale" :current="$locale" :items="[
                ['key' => 'it', 'label' => __('admin-content.common.lang_it')],
                ['key' => 'en', 'label' => __('admin-content.common.lang_en')],
            ]" />

            <div class="flex flex-col gap-5 p-5" wire:key="site-fields-{{ $locale }}">
                @if ($isOriginal)
                    <div class="flex gap-[11px] rounded-lg border border-gray-150 bg-[#FAFBFB] px-[15px] py-[13px]">
                        <flux:icon.exclamation-triangle class="mt-px size-4 shrink-0 text-gray-600" />
                        <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __('admin-content.site.still_original') }}</p>
                    </div>
                @endif

                @foreach ($fields as $field)
                    <flux:field wire:key="field-{{ $locale }}-{{ $field['index'] }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <flux:label class="!text-[13.5px] !font-bold !text-admin-rail">{{ $field['label'] }}</flux:label>
                            @if ($field['saved'])
                                <x-admin.button tone="ghost" size="sm" icon="arrow-uturn-left" class="!h-7 !px-2 !text-[12.5px]" wire:click="restore({{ $field['index'] }})">{{ __('admin-content.site.restore') }}</x-admin.button>
                            @endif
                        </div>

                        @if ($field['type'] === 'line')
                            <flux:input wire:model="values.{{ $locale }}.{{ $field['index'] }}" :placeholder="$field['original']" />
                        @else
                            <flux:textarea
                                wire:model="values.{{ $locale }}.{{ $field['index'] }}"
                                :placeholder="$field['original']"
                                :rows="$field['type'] === 'paragraphs' ? 9 : 3"
                                resize="vertical"
                            />
                        @endif

                        @if ($field['type'] === 'paragraphs')
                            <flux:description>{{ __('admin-content.site.paragraphs_hint') }}</flux:description>
                        @endif

                        <flux:error name="values.{{ $locale }}.{{ $field['index'] }}" />
                    </flux:field>
                @endforeach
            </div>
        </x-admin.card>

        <div class="flex min-w-0 flex-col gap-3.5">
            <x-admin.card :heading="__('admin-content.common.settings')">
                <div class="flex flex-col gap-4 p-5 text-[14px]">
                    <div>
                        <p class="m-0 text-[13.5px] font-bold text-admin-rail">{{ __('admin-content.common.page_type') }}</p>
                        <p class="mt-1.5 text-gray-600">{{ __('admin-content.site.type_site') }}</p>
                    </div>
                    <div>
                        <p class="m-0 text-[13.5px] font-bold text-admin-rail">{{ __('admin-content.common.address') }}</p>
                        <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="mt-1.5 inline-block text-admin-teal hover:underline">{{ $path }}</a>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.notice tone="warning" :heading="__('admin-content.site.fixed_heading')">
                {{ __('admin-content.site.fixed_body') }}
            </x-admin.notice>
        </div>
    </div>
</div>
