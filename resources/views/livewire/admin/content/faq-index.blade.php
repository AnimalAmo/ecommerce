{{-- Domande frequenti (design: is_faq). --}}

<div class="flex flex-col gap-[18px]">
    <x-admin.page-header
        :heading="__('admin-content.faq.title')"
        :sub="$tab === 'products' ? __('admin-content.faq.sub_products') : __('admin-content.faq.sub')"
    >
        <x-slot:actions>
            @if ($tab === 'platform')
                <x-admin.button tone="ghost" icon="arrow-top-right-on-square" :href="route('faq')" target="_blank">{{ __('admin-content.faq.view_page') }}</x-admin.button>
            @endif
            <x-admin.button tone="primary" icon="plus" wire:click="create">{{ __('admin-content.faq.new') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.tabs model="tab" :current="$tab" :items="[
        ['key' => 'platform', 'label' => __('admin-content.faq.tabs.platform')],
        ['key' => 'products', 'label' => __('admin-content.faq.tabs.products')],
    ]" />

    @forelse ($groups as $group)
        <x-admin.card wire:key="faq-group-{{ $group['key'] }}">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-150 px-5 py-4">
                <div class="flex min-w-0 flex-wrap items-center gap-2.5">
                    <h2 class="m-0 text-base font-bold text-admin-rail">{{ $group['label'] }}</h2>
                    @if ($group['family'])
                        <x-admin.badge :tone="$group['family'] === 'structure' ? 'info' : 'pink'">{{ __('admin-content.faq.families.'.$group['family']) }}</x-admin.badge>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    @if ($group['url'])
                        <a href="{{ $group['url'] }}" target="_blank" rel="noopener" class="text-[12.5px] font-semibold text-admin-teal hover:underline">{{ __('admin-content.faq.view_product') }}</a>
                    @endif
                    <span class="text-[12.5px] text-gray-400">{{ trans_choice('admin-content.faq.count', count($group['faqs'])) }}</span>
                </div>
            </header>

            <div wire:sort="sort">
                @foreach ($group['faqs'] as $faq)
                    <div wire:key="faq-{{ $faq['id'] }}" wire:sort:item="{{ $faq['id'] }}" class="flex items-start gap-3.5 border-b border-admin-row bg-white px-5 py-4 last:border-b-0">
                        <span wire:sort:handle class="mt-0.5 shrink-0 cursor-grab text-gray-300" title="{{ __('admin-content.faq.drag') }}" aria-label="{{ __('admin-content.faq.drag') }}">
                            <flux:icon.bars-3 class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="m-0 text-[14.5px] font-bold text-admin-rail">{{ $faq['question'] }}</p>
                            <p class="mt-1.5 line-clamp-3 whitespace-pre-line text-[13.5px] leading-normal text-gray-600">{{ $faq['answer'] }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <x-admin.badge :tone="$faq['english'] ? 'success' : 'warning'">{{ $faq['english'] ? __('admin-content.faq.langs_both') : __('admin-content.faq.langs_it') }}</x-admin.badge>
                            <x-admin.icon-action tone="edit" icon="pencil" :label="__('admin-content.common.edit')" wire:click="edit({{ $faq['id'] }})" />
                            <x-admin.icon-action tone="delete" icon="trash" :label="__('admin-content.common.delete')" wire:click="askDelete({{ $faq['id'] }})" />
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.card>
    @empty
        <x-admin.card>
            <x-admin.empty>{{ $tab === 'products' ? __('admin-content.faq.empty_products') : __('admin-content.faq.empty_platform') }}</x-admin.empty>
        </x-admin.card>
    @endforelse

    <flux:modal name="faq-editor" class="w-full max-w-[640px]" wire:close="closeEditor">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ $editingId ? __('admin-content.faq.edit_title') : __('admin-content.faq.create_title') }}</flux:heading>

            <div class="grid items-start gap-3.5 sm:grid-cols-2">
                <flux:select wire:model.live="scope" :label="__('admin-content.faq.field_scope')">
                    <flux:select.option value="platform">{{ __('admin-content.faq.scope_platform') }}</flux:select.option>
                    <flux:select.option value="product">{{ __('admin-content.faq.scope_product') }}</flux:select.option>
                </flux:select>

                @if ($scope === 'platform')
                    <flux:select wire:model="topic" :label="__('admin-content.faq.field_topic')">
                        @foreach (\App\Models\Faq\Faq::TOPICS as $value)
                            <flux:select.option value="{{ $value }}">{{ __('faq.topics.'.$value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:select wire:model="product" variant="listbox" searchable :label="__('admin-content.faq.field_product')" :placeholder="__('admin-content.faq.product_placeholder')">
                        @foreach ($productOptions as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
            </div>

            <flux:input wire:model="question.it" :label="__('admin-content.faq.field_question').' · '.__('admin-content.common.lang_it')" />
            <flux:textarea wire:model="answer.it" rows="4" resize="vertical" :label="__('admin-content.faq.field_answer').' · '.__('admin-content.common.lang_it')" />

            <flux:separator variant="subtle" />

            <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __('admin-content.faq.english_hint') }}</p>
            <flux:input wire:model="question.en" :label="__('admin-content.faq.field_question').' · '.__('admin-content.common.lang_en')" />
            <flux:textarea wire:model="answer.en" rows="4" resize="vertical" :label="__('admin-content.faq.field_answer').' · '.__('admin-content.common.lang_en')" />

            <div class="mt-2 flex flex-wrap justify-end gap-2.5">
                <flux:modal.close>
                    <x-admin.button>{{ __('admin-content.common.cancel') }}</x-admin.button>
                </flux:modal.close>
                <x-admin.button tone="primary" type="submit">{{ __('admin-content.faq.save') }}</x-admin.button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="faq-delete" class="w-full max-w-[460px]" wire:close="$set('deletingId', null)">
        <div class="flex flex-col">
            <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-content.faq.delete_title') }}</flux:heading>
            <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ $deletingBody }}</p>
            <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                <flux:modal.close>
                    <x-admin.button>{{ __('admin-content.common.cancel') }}</x-admin.button>
                </flux:modal.close>
                <x-admin.button tone="danger" wire:click="confirmDelete">{{ __('admin-content.common.delete') }}</x-admin.button>
            </div>
        </div>
    </flux:modal>
</div>
