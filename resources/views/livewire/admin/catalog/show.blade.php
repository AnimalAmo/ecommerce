<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.catalog.index')">Torna al catalogo</x-admin.back-link>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex min-w-0 items-center gap-4">
            <span aria-hidden="true" class="h-[72px] w-24 shrink-0 rounded-lg bg-brand-cyan-bg bg-cover bg-center" @if ($row['img']) style="background-image: url('{{ $row['img'] }}')" @endif></span>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="m-0 text-[25px] leading-tight font-bold text-admin-rail">{{ $row['name'] }}</h1>
                    <x-admin.badge :tone="$row['statusTone']">{{ $row['statusLabel'] }}</x-admin.badge>
                    <x-admin.badge :tone="$row['typeTone']">{{ $row['type'] }}</x-admin.badge>
                </div>
                <p class="mt-2 text-[15px] text-gray-600">{{ $row['partner'] }} · {{ $row['place'] }} · pubblicata il {{ $publishedOn }}</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2.5">
            @if ($publicUrl)
                <x-admin.button tone="ghost" icon="arrow-top-right-on-square" :href="$publicUrl" target="_blank">Vedi sul sito</x-admin.button>
            @endif
            <x-admin.button
                :icon="$row['suspended'] ? 'play' : 'pause'"
                wire:click="askSuspend('{{ $row['family'] }}', {{ $row['id'] }})"
            >{{ $row['suspended'] ? 'Riattiva scheda' : 'Sospendi scheda' }}</x-admin.button>
            <x-admin.button tone="primary" wire:click="save">Salva modifiche</x-admin.button>
        </div>
    </div>

    @if ($item->approval_status === 'changes_requested' && filled($item->approval_note))
        <x-admin.notice tone="warning" heading="Hai chiesto modifiche al partner">
            «{{ $item->approval_note }}» — la scheda resta fuori dal sito finché il partner non la ripubblica.
        </x-admin.notice>
    @endif

    <div class="grid items-start gap-3.5 lg:grid-cols-3">
        <x-admin.card heading="Testi pubblicati" class="lg:col-span-2">
            <x-slot:aside>
                <span class="text-[12.5px] text-gray-400">{{ $lang === 'it' ? 'Italiano' : 'Inglese' }}</span>
            </x-slot:aside>

            <x-admin.tabs class="px-5" model="lang" :current="$lang" :items="[['key' => 'it', 'label' => 'Italiano'], ['key' => 'en', 'label' => 'Inglese']]" />

            <form wire:submit="save" class="flex flex-col gap-4 p-5">
                @foreach (['it', 'en'] as $locale)
                    <div wire:key="texts-{{ $locale }}" @class(['flex flex-col gap-4', 'hidden' => $lang !== $locale])>
                        <flux:input wire:model="name.{{ $locale }}" label="Nome della scheda" :placeholder="$locale === 'en' ? 'Lascia vuoto per mostrare il nome italiano' : ''" />
                        <flux:textarea wire:model="description.{{ $locale }}" label="Descrizione" rows="6" resize="vertical" :placeholder="$locale === 'en' ? 'Lascia vuoto per mostrare la descrizione italiana' : ''" />
                    </div>
                @endforeach

                <div class="grid items-start gap-3.5 sm:grid-cols-3">
                    <flux:input
                        wire:model="price"
                        :label="$isStructure ? 'Prezzo a notte (€)' : ($isEvent ? 'Prezzo a persona (€)' : 'Prezzo (€)')"
                        :placeholder="$isEvent ? 'Vuoto = gratis' : ''"
                        inputmode="decimal"
                    />
                    @if ($isStructure)
                        <flux:input wire:model="supplement" label="Supplemento animale (€)" inputmode="decimal" />
                        <flux:select wire:model="regionId" label="Regione">
                            <flux:select.option value="">Nessuna</flux:select.option>
                            @foreach ($regions as $id => $name)
                                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                    <flux:input wire:model="cancellationDays" type="number" min="0" max="365" label="Cancellazione gratuita (giorni prima)" />
                </div>

                @if ($isStructure && $item->structure_draft_id)
                    <p class="m-0 text-[12.5px] leading-normal text-gray-400">Il prezzo a notte nasce dalle camere inserite dal partner: se il partner ripubblica la scheda, torna al prezzo della sua camera più economica. Nome e descrizione invece restano quelli che salvi qui.</p>
                @endif
            </form>
        </x-admin.card>

        <div class="flex min-w-0 flex-col gap-3.5">
            <x-admin.card heading="Sul sito">
                @foreach ($stats as $stat)
                    <div class="flex items-center justify-between gap-3 border-b border-admin-row px-5 py-3 last:border-b-0">
                        <span class="text-[13.5px] text-gray-600">{{ $stat['label'] }}</span>
                        <span class="text-[14.5px] font-bold text-admin-rail">{{ $stat['value'] }}</span>
                    </div>
                @endforeach
            </x-admin.card>

            @if ($blocker)
                <x-admin.notice tone="warning" heading="La cancellazione è bloccata">
                    {{ $blocker }}
                </x-admin.notice>
            @else
                <x-admin.card>
                    <div class="flex flex-col gap-3 px-5 py-4">
                        <p class="m-0 text-[13px] leading-normal text-gray-600">Nessuna prenotazione futura: puoi eliminare la scheda definitivamente. Se vuoi solo toglierla dal sito, sospendila.</p>
                        <x-admin.button tone="danger" icon="trash" class="self-start" wire:click="askDelete('{{ $row['family'] }}', {{ $row['id'] }})">Elimina scheda</x-admin.button>
                    </div>
                </x-admin.card>
            @endif
        </div>
    </div>

    @include('livewire.admin.catalog.partials.confirm-modal')
</div>
