{{-- Animal Times: elenco articoli (design: is_animaltimes). --}}
<div class="flex flex-col gap-[18px]">
    <x-admin.page-header :heading="__('admin-content.articles.title')" :sub="$sub">
        <x-slot:actions>
            <x-admin.button tone="primary" icon="plus" :href="route('admin.articles.create')" wire:navigate>{{ __('admin-content.articles.new') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card>
        <x-admin.filters>
            <div class="min-w-[190px] max-w-[340px] flex-1">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('admin-content.articles.search_placeholder')" :aria-label="__('admin-content.articles.search_label')" />
            </div>
            <div class="min-w-[150px]">
                <flux:select wire:model.live="status" :aria-label="__('admin-content.articles.filter_status_label')">
                    <flux:select.option value="">{{ __('admin-content.articles.filter_status_all') }}</flux:select.option>
                    @foreach (__('admin-content.articles.status_filters') as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="min-w-[150px]">
                <flux:select wire:model.live="category" :aria-label="__('admin-content.articles.filter_category_label')">
                    <flux:select.option value="">{{ __('admin-content.articles.filter_category_all') }}</flux:select.option>
                    @foreach (\App\Models\Article\Article::CATEGORIES as $value)
                        <flux:select.option value="{{ $value }}">{{ __('admin-content.articles.categories.'.$value) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </x-admin.filters>

        @if ($page->isEmpty())
            <x-admin.empty>{{ __('admin-content.articles.empty') }}</x-admin.empty>
        @else
            <div class="overflow-x-auto">
                <flux:table class="min-w-[max(100%,860px)]">
                    <flux:table.columns>
                        <flux:table.column class="!pl-5">{{ __('admin-content.articles.columns.article') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-content.articles.columns.category') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-content.articles.columns.languages') }}</flux:table.column>
                        <flux:table.column>{{ __('admin-content.articles.columns.published') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('admin-content.articles.columns.views') }}</flux:table.column>
                        <flux:table.column align="end" class="!pr-5">{{ __('admin-content.common.actions') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($page as $article)
                            @php
                                $status = $article->status();
                                $editUrl = route('admin.articles.edit', $article);
                            @endphp
                            <flux:table.row :key="'article-'.$article->id">
                                <flux:table.cell class="!pl-5">
                                    <a href="{{ $editUrl }}" wire:navigate class="flex items-center gap-3">
                                        <x-article-cover :article="$article" class="h-11 w-16 shrink-0 rounded-md object-cover" />
                                        <span class="flex min-w-0 flex-col gap-[3px]">
                                            <span class="text-[15px] font-bold text-admin-rail">{{ $article->titleFor('it') }}</span>
                                            <x-admin.badge :tone="$statusTones[$status]" class="self-start">{{ __('admin-content.articles.statuses.'.$status) }}</x-admin.badge>
                                        </span>
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell>{{ $article->category ? __('admin-content.articles.categories.'.$article->category) : '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $article->hasLocale('en') ? 'IT, EN' : 'IT' }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $article->published_at ? $article->published_at->locale('it')->translatedFormat('j M Y') : '—' }}</flux:table.cell>
                                <flux:table.cell align="end">{{ $article->views > 0 ? number_format($article->views, 0, ',', '.') : '—' }}</flux:table.cell>
                                <flux:table.cell class="!pr-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-admin.icon-action tone="edit" icon="pencil" :label="__('admin-content.common.edit')" :href="$editUrl" wire:navigate />
                                        @if ($status === \App\Models\Article\Article::PUBLISHED)
                                            <x-admin.icon-action tone="view" icon="arrow-top-right-on-square" :label="__('admin-content.common.view_on_site')" :href="route('news.detail', $article->slug)" target="_blank" />
                                        @endif
                                        <x-admin.icon-action tone="delete" icon="trash" :label="__('admin-content.common.delete')" wire:click="askDelete({{ $article->id }})" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2.5 px-5 py-3.5">
                <span class="text-[13.5px] text-gray-400">{{ trans_choice('admin-content.articles.count', $page->total()) }}</span>
                <div>{{ $page->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-admin.card>

    <flux:modal name="article-delete" class="w-full max-w-[460px]" wire:close="$set('deleting', null)">
        <div class="flex flex-col">
            <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-content.articles.delete_title') }}</flux:heading>
            <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ __('admin-content.articles.delete_body', ['title' => (string) $deletingTitle]) }}</p>
            <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                <flux:modal.close>
                    <x-admin.button>{{ __('admin-content.common.cancel') }}</x-admin.button>
                </flux:modal.close>
                <x-admin.button tone="danger" wire:click="confirmDelete">{{ __('admin-content.common.delete') }}</x-admin.button>
            </div>
        </div>
    </flux:modal>
</div>
