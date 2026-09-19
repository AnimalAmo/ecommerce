@php
    use App\Services\Admin\People\UserDirectory;

    $th = '!text-[13.5px] !font-semibold !text-gray-400';
    $initials = fn ($u) => mb_strtoupper(mb_substr((string) $u->first_name, 0, 1).mb_substr((string) $u->last_name, 0, 1));
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.page-header
        heading="Iscritti"
        sub="{{ number_format($totals['users'], 0, ',', '.') }} {{ $totals['users'] === 1 ? 'utente registrato' : 'utenti registrati' }}. {{ number_format($totals['newsletter'], 0, ',', '.') }} {{ $totals['newsletter'] === 1 ? 'ha' : 'hanno' }} chiesto la newsletter."
    >
        <x-slot:actions>
            <x-admin.button tone="outline" icon="arrow-down-tray" :href="route('admin.users.export', $exportQuery)">Esporta in Excel</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card>
        <x-admin.filters>
            <flux:input
                wire:model.live.debounce.300ms="q"
                icon="magnifying-glass"
                type="search"
                placeholder="Cerca per nome o email"
                aria-label="Cerca per nome o email"
                class="min-w-[190px] flex-1 sm:max-w-[340px]"
            />
            <flux:select wire:model.live="newsletter" aria-label="Newsletter" class="!w-auto min-w-[170px]">
                <flux:select.option value="all">Tutti gli iscritti</flux:select.option>
                <flux:select.option value="with">Con newsletter</flux:select.option>
                <flux:select.option value="without">Senza newsletter</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="status" aria-label="Stato" class="!w-auto min-w-[150px]">
                <flux:select.option value="all">Tutti gli stati</flux:select.option>
                <flux:select.option value="active">Attivo</flux:select.option>
                <flux:select.option value="inactive">Disattivato</flux:select.option>
                <flux:select.option value="anonymized">Anonimizzato</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="role" aria-label="Ruolo" class="!w-auto min-w-[140px]">
                <flux:select.option value="all">Tutti i ruoli</flux:select.option>
                <flux:select.option value="client">Clienti</flux:select.option>
                <flux:select.option value="partner">Partner</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="period" aria-label="Periodo di iscrizione" class="!w-auto min-w-[160px]">
                <flux:select.option value="always">Iscritti: sempre</flux:select.option>
                <flux:select.option value="30d">Ultimi 30 giorni</flux:select.option>
                <flux:select.option value="year">Quest'anno</flux:select.option>
            </flux:select>
        </x-admin.filters>

        @if ($users->isEmpty())
            <x-admin.empty>Nessun iscritto corrisponde ai filtri.</x-admin.empty>
        @else
            <div class="px-5 pb-2">
                <flux:table :paginate="$users" class="min-w-[900px]">
                    <flux:table.columns>
                        <flux:table.column :class="$th" sortable :sorted="$sort === 'name'" :direction="$dir" wire:click="sortBy('name')">Utente</flux:table.column>
                        <flux:table.column :class="$th" sortable :sorted="$sort === 'created_at'" :direction="$dir" wire:click="sortBy('created_at')">Iscritto il</flux:table.column>
                        <flux:table.column :class="$th">Newsletter</flux:table.column>
                        <flux:table.column :class="$th" align="end" sortable :sorted="$sort === 'orders'" :direction="$dir" wire:click="sortBy('orders')">Ordini</flux:table.column>
                        <flux:table.column :class="$th" align="end" sortable :sorted="$sort === 'spent'" :direction="$dir" wire:click="sortBy('spent')">Speso</flux:table.column>
                        <flux:table.column :class="$th">Stato</flux:table.column>
                        <flux:table.column :class="$th" align="end">Azioni</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($users as $u)
                            @php $isPartner = $u->roles->contains('name', 'partner'); @endphp
                            <flux:table.row :key="$u->id">
                                <flux:table.cell>
                                    <a href="{{ route('admin.users.show', $u) }}" wire:navigate class="flex items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-cyan-bg text-[12.5px] font-bold text-admin-teal">{{ $initials($u) }}</span>
                                        <span class="flex min-w-0 flex-col gap-0.5">
                                            <span class="flex items-center gap-2">
                                                <span class="text-[15px] font-bold text-admin-rail">{{ $u->name }}</span>
                                                @if ($isPartner)
                                                    <x-admin.badge tone="purple">Partner</x-admin.badge>
                                                @else
                                                    <x-admin.badge tone="muted">Cliente</x-admin.badge>
                                                @endif
                                            </span>
                                            <span class="text-[12.5px] text-gray-400">{{ $u->email }}</span>
                                        </span>
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell class="!text-gray-600">{{ $u->created_at?->locale('it')->isoFormat('D MMM YYYY') }}</flux:table.cell>
                                <flux:table.cell>
                                    @switch($u->newsletter_state)
                                        @case('confirmed')
                                            <x-admin.badge tone="success">Sì</x-admin.badge>
                                            @break
                                        @case('pending')
                                            <x-admin.badge tone="warning">In attesa</x-admin.badge>
                                            @break
                                        @default
                                            <x-admin.badge tone="muted">No</x-admin.badge>
                                    @endswitch
                                </flux:table.cell>
                                <flux:table.cell align="end">{{ (int) $u->paid_orders_count }}</flux:table.cell>
                                <flux:table.cell align="end">{{ UserDirectory::money((int) $u->spent_cents) }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($u->anonymized_at !== null)
                                        <x-admin.badge tone="muted">Anonimizzato</x-admin.badge>
                                    @elseif ($u->is_active)
                                        <x-admin.badge tone="info">Attivo</x-admin.badge>
                                    @else
                                        <x-admin.badge tone="warning">Disattivato</x-admin.badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-admin.icon-action tone="view" icon="eye" label="Apri scheda" :href="route('admin.users.show', $u)" wire:navigate />
                                        @if ($u->anonymized_at === null)
                                            <x-admin.icon-action tone="delete" icon="trash" label="Cancella su richiesta" wire:click="askAnonymize({{ $u->id }})" />
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </x-admin.card>

    @include('livewire.admin.people.partials.anonymize-modal')
</div>
