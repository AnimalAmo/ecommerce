<div class="flex flex-col gap-[18px]">
    {{-- Arriva da CreatesPartnerService::save(): il pannello non ha un canale
         flash e un flux:toast non sopravvive al redirect, quindi la conferma
         viaggia in sessione. Senza, l'admin atterra qui senza sapere se la
         scheda è andata a buon fine (contratto §Chiavi lang, create.published). --}}
    @if (session('catalog_created'))
        <x-admin.notice tone="info">{{ session('catalog_created') }}</x-admin.notice>
    @endif

    <x-admin.back-link :href="route('admin.catalog.index')">{{ __('admin-catalog.show.back') }}</x-admin.back-link>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex min-w-0 items-center gap-4">
            <span aria-hidden="true" class="h-[72px] w-24 shrink-0 rounded-lg bg-brand-cyan-bg bg-cover bg-center" @if ($row['img']) style="background-image: url('{{ $row['img'] }}')" @endif></span>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="m-0 text-[25px] leading-tight font-bold text-admin-rail">{{ $row['name'] }}</h1>
                    <x-admin.badge :tone="$row['statusTone']">{{ $row['statusLabel'] }}</x-admin.badge>
                    <x-admin.badge :tone="$row['typeTone']">{{ $row['type'] }}</x-admin.badge>
                </div>
                <p class="mt-2 text-[15px] text-gray-600">{{ __('admin-catalog.show.meta', ['partner' => $row['partner'], 'place' => $row['place'], 'date' => $publishedOn]) }}</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2.5">
            @if ($publicUrl)
                <x-admin.button tone="ghost" icon="arrow-top-right-on-square" :href="$publicUrl" target="_blank">{{ __('admin-catalog.show.view_on_site') }}</x-admin.button>
            @endif
            <x-admin.button
                :icon="$row['suspended'] ? 'play' : 'pause'"
                wire:click="askSuspend('{{ $row['family'] }}', {{ $row['id'] }})"
            >{{ __($row['suspended'] ? 'admin-catalog.show.reactivate' : 'admin-catalog.show.suspend') }}</x-admin.button>
            <x-admin.button tone="primary" wire:click="save">{{ __('admin-catalog.show.save') }}</x-admin.button>
        </div>
    </div>

    @if ($item->approval_status === 'changes_requested' && filled($item->approval_note))
        <x-admin.notice tone="warning" :heading="__('admin-catalog.show.changes_heading')">
            {{ __('admin-catalog.show.changes_body', ['note' => $item->approval_note]) }}
        </x-admin.notice>
    @endif

    <div class="grid items-start gap-3.5 lg:grid-cols-3">
        <x-admin.card :heading="__('admin-catalog.show.texts')" class="lg:col-span-2">
            <x-slot:aside>
                <span class="text-[12.5px] text-gray-400">{{ __($lang === 'it' ? 'admin-catalog.show.lang_it' : 'admin-catalog.show.lang_en') }}</span>
            </x-slot:aside>

            <x-admin.tabs class="px-5" model="lang" :current="$lang" :items="[['key' => 'it', 'label' => __('admin-catalog.show.lang_it')], ['key' => 'en', 'label' => __('admin-catalog.show.lang_en')]]" />

            <form wire:submit="save" class="flex flex-col gap-4 p-5">
                @foreach (['it', 'en'] as $locale)
                    <div wire:key="texts-{{ $locale }}" @class(['flex flex-col gap-4', 'hidden' => $lang !== $locale])>
                        <flux:input wire:model="name.{{ $locale }}" :label="__('admin-catalog.show.name')" :placeholder="$locale === 'en' ? __('admin-catalog.show.name_en_placeholder') : ''" />
                        <flux:textarea wire:model="description.{{ $locale }}" :label="__('admin-catalog.show.description')" rows="6" resize="vertical" :placeholder="$locale === 'en' ? __('admin-catalog.show.description_en_placeholder') : ''" />
                    </div>
                @endforeach

                <div class="grid items-start gap-3.5 sm:grid-cols-3">
                    <flux:input
                        wire:model="price"
                        :label="__($isStructure ? 'admin-catalog.show.price_night' : ($isEvent ? 'admin-catalog.show.price_person' : 'admin-catalog.show.price'))"
                        :placeholder="$isEvent ? __('admin-catalog.show.price_free_placeholder') : ''"
                        inputmode="decimal"
                    />
                    @if ($isStructure)
                        <flux:input wire:model="supplement" :label="__('admin-catalog.show.supplement')" inputmode="decimal" />
                        <flux:select wire:model="regionId" :label="__('admin-catalog.show.region')">
                            <flux:select.option value="">{{ __('admin-catalog.show.region_none') }}</flux:select.option>
                            @foreach ($regions as $id => $name)
                                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                    <flux:input wire:model="cancellationDays" type="number" min="0" max="365" :label="__('admin-catalog.show.cancellation')" />
                </div>

                @if ($isStructure && $item->structure_draft_id)
                    <p class="m-0 text-[12.5px] leading-normal text-gray-400">{{ __('admin-catalog.show.draft_price_note') }}</p>
                @endif
            </form>
        </x-admin.card>

        <div class="flex min-w-0 flex-col gap-3.5">
            <x-admin.card :heading="__('admin-catalog.show.on_site')">
                @foreach ($stats as $stat)
                    <div class="flex items-center justify-between gap-3 border-b border-admin-row px-5 py-3 last:border-b-0">
                        <span class="text-[13.5px] text-gray-600">{{ $stat['label'] }}</span>
                        <span class="text-[14.5px] font-bold text-admin-rail">{{ $stat['value'] }}</span>
                    </div>
                @endforeach
            </x-admin.card>

            @if ($blocker)
                <x-admin.notice tone="warning" :heading="__('admin-catalog.show.blocked_heading')">
                    {{ $blocker }}
                </x-admin.notice>
            @else
                <x-admin.card>
                    <div class="flex flex-col gap-3 px-5 py-4">
                        <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __('admin-catalog.show.delete_note') }}</p>
                        <x-admin.button tone="danger" icon="trash" class="self-start" wire:click="askDelete('{{ $row['family'] }}', {{ $row['id'] }})">{{ __('admin-catalog.show.delete') }}</x-admin.button>
                    </div>
                </x-admin.card>
            @endif
        </div>
    </div>

    @include('livewire.admin.catalog.partials.confirm-modal')
</div>
