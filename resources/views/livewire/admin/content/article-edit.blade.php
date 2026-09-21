{{-- Animal Times: editor di un articolo (design: is_articolo). --}}
<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.articles.index')">{{ __('admin-content.articles.back') }}</x-admin.back-link>

    <div class="flex flex-wrap items-end justify-between gap-3.5">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="m-0 text-[25px] leading-tight font-bold text-admin-rail">{{ $heading }}</h1>
                @if ($status)
                    <x-admin.badge :tone="$statusTones[$status]">{{ __('admin-content.articles.statuses.'.$status) }}</x-admin.badge>
                @endif
            </div>
            <p class="mt-2 text-[15px] text-gray-600">{{ $sub }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            @if ($publicUrl)
                <x-admin.button tone="ghost" icon="arrow-top-right-on-square" :href="$publicUrl" target="_blank">{{ __('admin-content.common.view_on_site') }}</x-admin.button>
            @endif
            <x-admin.button icon="eye" wire:click="preview">{{ __('admin-content.common.preview') }}</x-admin.button>
            @if ($status === null || $status === \App\Models\Article\Article::DRAFT)
                <x-admin.button wire:click="saveDraft">{{ __('admin-content.articles.save_draft') }}</x-admin.button>
                <x-admin.button tone="primary" wire:click="publish">{{ __('admin-content.articles.publish') }}</x-admin.button>
            @else
                <x-admin.button tone="ghost" wire:click="unpublish">{{ __('admin-content.articles.unpublish') }}</x-admin.button>
                <x-admin.button tone="primary" wire:click="save">{{ __('admin-content.articles.save') }}</x-admin.button>
            @endif
        </div>
    </div>

    <div class="grid items-start gap-3.5 lg:grid-cols-3">
        <x-admin.card class="lg:col-span-2">
            <x-admin.tabs class="px-5" model="locale" :current="$locale" :items="[
                ['key' => 'it', 'label' => __('admin-content.common.lang_it')],
                ['key' => 'en', 'label' => __('admin-content.common.lang_en')],
            ]" />

            <div class="p-5">
                {{-- Entrambe le lingue restano nel DOM: l'editor non si reinizializza al cambio scheda. --}}
                @foreach (['it', 'en'] as $lang)
                    <div wire:key="article-fields-{{ $lang }}" @class(['flex flex-col gap-4', 'hidden' => $locale !== $lang])>
                        @if ($lang === 'en')
                            <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __('admin-content.articles.english_fallback') }}</p>
                        @endif

                        <flux:input wire:model="title.{{ $lang }}" :label="__('admin-content.articles.field_title')" />

                        <flux:textarea
                            wire:model="excerpt.{{ $lang }}"
                            :label="__('admin-content.articles.field_excerpt')"
                            :description:trailing="__('admin-content.articles.excerpt_hint')"
                            rows="2"
                            resize="vertical"
                        />

                        <flux:editor
                            wire:model="body.{{ $lang }}"
                            :label="__('admin-content.articles.field_body')"
                            toolbar="heading | bold italic underline strike | bullet ordered blockquote | link ~ undo redo"
                            :placeholder="__('admin-content.articles.body_placeholder_'.$lang)"
                            class="**:data-[slot=content]:min-h-[320px]"
                        />
                    </div>
                @endforeach
            </div>
        </x-admin.card>

        <div class="flex min-w-0 flex-col gap-3.5">
            <x-admin.card :heading="__('admin-content.articles.cover')">
                <div class="flex flex-col gap-3 p-5">
                    @if ($coverPreview)
                        <img src="{{ $coverPreview }}" alt="" class="block h-[150px] w-full rounded-lg bg-gray-100 object-cover">
                    @else
                        <div class="flex h-[150px] w-full items-center justify-center rounded-lg bg-gray-100 text-[13px] text-gray-400">{{ __('admin-content.articles.no_cover') }}</div>
                    @endif

                    @if ($cover)
                        <p class="m-0 text-[12.5px] text-admin-teal">{{ __('admin-content.articles.cover_new') }}</p>
                    @endif

                    <flux:file-upload wire:model="cover" accept="image/jpeg,image/png,image/webp" class="w-full">
                        <div class="flex h-10 w-full cursor-pointer items-center justify-center rounded-full border border-gray-300 bg-white text-[14.5px] font-semibold text-admin-rail hover:bg-gray-100">
                            {{ $coverPreview ? __('admin-content.articles.cover_replace') : __('admin-content.articles.cover_choose') }}
                        </div>
                    </flux:file-upload>
                    <p wire:loading wire:target="cover" class="m-0 text-[12.5px] text-gray-400">{{ __('admin-content.articles.cover_uploading') }}</p>
                    <flux:error name="cover" />

                    <p class="m-0 text-[12.5px] leading-normal text-gray-400">{{ __('admin-content.articles.cover_hint') }}</p>

                    <flux:input
                        wire:model="coverAlt.{{ $locale }}"
                        wire:key="cover-alt-{{ $locale }}"
                        :label="__('admin-content.articles.cover_alt').' · '.($locale === 'it' ? __('admin-content.common.lang_it') : __('admin-content.common.lang_en'))"
                        :description:trailing="__('admin-content.articles.cover_alt_hint')"
                    />
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-content.articles.publication')">
                <div class="flex flex-col gap-4 p-5">
                    <flux:select wire:model="category" :label="__('admin-content.articles.field_category')">
                        <flux:select.option value="">{{ __('admin-content.articles.category_none') }}</flux:select.option>
                        @foreach (\App\Models\Article\Article::CATEGORIES as $value)
                            <flux:select.option value="{{ $value }}">{{ __('admin-content.articles.categories.'.$value) }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input type="date" wire:model="publishedAt" :label="__('admin-content.articles.field_date')" :description:trailing="__('admin-content.articles.date_hint')" />

                    <flux:input
                        wire:model="slug"
                        :label="__('admin-content.articles.field_slug')"
                        :description:trailing="$slugPrefix.($slug !== '' ? $slug : '…').' — '.__('admin-content.articles.slug_hint')"
                    />
                </div>
            </x-admin.card>

            @if ($article)
                <flux:modal.trigger name="article-delete">
                    <x-admin.button tone="danger" icon="trash" class="self-start">{{ __('admin-content.articles.delete') }}</x-admin.button>
                </flux:modal.trigger>
            @endif
        </div>
    </div>

    <flux:modal name="article-preview" class="w-full max-w-[980px]" wire:close="$set('previewing', false)">
        <div class="flex flex-col">
            <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-content.common.preview') }} · {{ $locale === 'it' ? __('admin-content.common.lang_it') : __('admin-content.common.lang_en') }}</flux:heading>
            <p class="mt-2 text-[13px] text-gray-600">{{ __('admin-content.articles.preview_note') }}</p>

            @if ($preview)
                @if ($preview['fallback'])
                    <p class="mt-2 text-[13px] text-gray-600">{{ __('admin-content.pages.preview_fallback') }}</p>
                @endif

                <div class="mt-5 max-h-[65vh] overflow-y-auto rounded-lg border border-gray-150 bg-white px-6 py-5">
                    <h2 class="text-3xl font-bold text-black">{{ $preview['title'] }}</h2>
                    @if ($preview['excerpt'] !== '')
                        <p class="mt-3 text-[15px] text-gray-600">{{ $preview['excerpt'] }}</p>
                    @endif
                    <div class="mt-4 flex flex-col-reverse items-start gap-6 xl:flex-row">
                        {{-- HTML filtrato da HtmlSanitizer, come al salvataggio. --}}
                        <div class="article-content min-w-0 flex-1">{!! $preview['html'] !!}</div>
                        @if ($preview['cover'])
                            <img src="{{ $preview['cover'] }}" alt="" class="h-[226px] w-[310px] shrink-0 rounded-[4px] object-cover">
                        @endif
                    </div>
                    @if ($preview['html'] === '')
                        <x-admin.empty>{{ __('admin-content.pages.preview_empty') }}</x-admin.empty>
                    @endif
                </div>
            @endif
        </div>
    </flux:modal>

    @if ($article)
        <flux:modal name="article-delete" class="w-full max-w-[460px]">
            <div class="flex flex-col">
                <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-content.articles.delete_title') }}</flux:heading>
                <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ __('admin-content.articles.delete_body', ['title' => $heading]) }}</p>
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
