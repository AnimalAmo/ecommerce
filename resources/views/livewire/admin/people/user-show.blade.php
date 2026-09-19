@php
    use App\Enums\OrderStatus;
    use App\Models\Partner\PartnerApplication;
    use App\Services\Admin\People\UserDirectory;
    use Illuminate\Support\Carbon;

    $th = '!text-[13.5px] !font-semibold !text-gray-400';
    $anonymizedAt = $user->anonymized_at !== null ? Carbon::parse($user->anonymized_at) : null;
    $date = fn ($d) => $d?->locale('it')->isoFormat('D MMM YYYY');
    $row = 'flex flex-wrap gap-x-4 gap-y-1 border-b border-admin-row py-[9px] last:border-b-0';
    $label = 'flex-[0_0_150px] text-[13px] text-gray-400';
    $value = 'min-w-0 flex-1 text-sm font-semibold break-words text-admin-rail';
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.users.index')">Torna agli iscritti</x-admin.back-link>

    <x-admin.page-header heading="{{ $user->name }}" sub="{{ $user->email }}">
        <x-slot:actions>
            @if ($anonymizedAt === null)
                <x-admin.button tone="outline" :icon="$user->is_active ? 'no-symbol' : 'check'" wire:click="toggleActive">
                    {{ $user->is_active ? 'Disattiva account' : 'Riattiva account' }}
                </x-admin.button>
                <x-admin.button tone="danger" icon="trash" wire:click="askAnonymize">Cancella su richiesta</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($anonymizedAt !== null)
        <x-admin.notice heading="Dati cancellati su richiesta">
            I dati personali di questo account sono stati cancellati il {{ $date($anonymizedAt) }}. Restano gli ordini, per gli obblighi fiscali. L'account non può più accedere.
        </x-admin.notice>
    @endif

    <div class="grid items-start gap-3.5 lg:grid-cols-2">
        <x-admin.card heading="Profilo">
            <div class="px-5 py-3">
                <div class="{{ $row }}"><span class="{{ $label }}">Nome</span><span class="{{ $value }}">{{ $user->first_name }}</span></div>
                <div class="{{ $row }}"><span class="{{ $label }}">Cognome</span><span class="{{ $value }}">{{ $user->last_name ?: '—' }}</span></div>
                <div class="{{ $row }}"><span class="{{ $label }}">Email</span><span class="{{ $value }}">{{ $user->email }}</span></div>
                <div class="{{ $row }}"><span class="{{ $label }}">Telefono</span><span class="{{ $value }}">{{ $user->phone ?: '—' }}</span></div>
                <div class="{{ $row }}"><span class="{{ $label }}">Data di nascita</span><span class="{{ $value }}">{{ $user->birth_date?->format('d/m/Y') ?? '—' }}</span></div>
                <div class="{{ $row }}">
                    <span class="{{ $label }}">Indirizzo</span>
                    <span class="{{ $value }}">{{ collect([$user->address, trim($user->postal_code.' '.$user->city)])->filter()->implode(', ') ?: '—' }}</span>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card heading="Account">
            <div class="px-5 py-3">
                <div class="{{ $row }}">
                    <span class="{{ $label }}">Ruolo</span>
                    <span class="{{ $value }}">
                        @if ($isPartner)
                            <x-admin.badge tone="purple">Partner</x-admin.badge>
                        @else
                            <x-admin.badge tone="muted">Cliente</x-admin.badge>
                        @endif
                    </span>
                </div>
                <div class="{{ $row }}">
                    <span class="{{ $label }}">Stato</span>
                    <span class="{{ $value }}">
                        @if ($anonymizedAt !== null)
                            <x-admin.badge tone="muted">Anonimizzato</x-admin.badge>
                        @elseif ($user->is_active)
                            <x-admin.badge tone="info">Attivo</x-admin.badge>
                        @else
                            <x-admin.badge tone="warning">Disattivato</x-admin.badge>
                        @endif
                    </span>
                </div>
                <div class="{{ $row }}"><span class="{{ $label }}">Iscritto il</span><span class="{{ $value }}">{{ $date($user->created_at) }}</span></div>
                <div class="{{ $row }}">
                    <span class="{{ $label }}">Newsletter</span>
                    <span class="{{ $value }}">
                        @switch($newsletterState)
                            @case('confirmed')
                                <x-admin.badge tone="success">Iscritto</x-admin.badge>
                                @break
                            @case('pending')
                                <x-admin.badge tone="warning">In attesa di conferma</x-admin.badge>
                                @break
                            @case('unsubscribed')
                                <x-admin.badge tone="muted">Disiscritto</x-admin.badge>
                                @break
                            @case(null)
                                <x-admin.badge tone="muted">No</x-admin.badge>
                                @break
                            @default
                                <x-admin.badge tone="danger">Non recapitabile</x-admin.badge>
                        @endswitch
                    </span>
                </div>
                <div class="{{ $row }}"><span class="{{ $label }}">Consenso marketing</span><span class="{{ $value }}">{{ $user->marketing_consent ? 'Sì' : 'No' }}</span></div>
            </div>
            @if ($anonymizedAt === null)
                <p class="m-0 border-t border-gray-150 px-5 py-3 text-[12.5px] leading-normal text-gray-400">
                    Un account disattivato non entra nell'area partner. Per chiudere del tutto un account usa "Cancella su richiesta".
                </p>
            @endif
        </x-admin.card>
    </div>

    <x-admin.card heading="Ordini">
        <x-slot:aside>
            <span class="text-[13px] text-gray-400">
                {{ $orders->count() }} {{ $orders->count() === 1 ? 'ordine' : 'ordini' }} ·
                pagati {{ UserDirectory::money((int) $orders->where('status', OrderStatus::Paid)->sum('total_cents')) }}
            </span>
        </x-slot:aside>
        @if ($orders->isEmpty())
            <x-admin.empty>Nessun ordine.</x-admin.empty>
        @else
            <div class="px-5 pb-2">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column :class="$th">Numero</flux:table.column>
                        <flux:table.column :class="$th">Data</flux:table.column>
                        <flux:table.column :class="$th" align="end">Totale</flux:table.column>
                        <flux:table.column :class="$th">Stato</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($orders as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell class="!font-semibold !text-admin-rail">{{ $order->order_number }}</flux:table.cell>
                                <flux:table.cell class="!text-gray-600">{{ $date($order->created_at) }}</flux:table.cell>
                                <flux:table.cell align="end">{{ UserDirectory::money((int) $order->total_cents) }}</flux:table.cell>
                                <flux:table.cell>
                                    <x-admin.badge :tone="match ($order->status) { OrderStatus::Paid => 'success', OrderStatus::Pending => 'warning', default => 'muted' }">
                                        {{ match ($order->status) { OrderStatus::Paid => 'Pagato', OrderStatus::Pending => 'In attesa', OrderStatus::Cancelled => 'Annullato' } }}
                                    </x-admin.badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </x-admin.card>

    <div class="grid items-start gap-3.5 lg:grid-cols-2">
        <x-admin.card heading="Animali">
            @if ($pets->isEmpty())
                <x-admin.empty>Nessun animale registrato.</x-admin.empty>
            @else
                <div class="px-5 py-3">
                    @foreach ($pets as $pet)
                        <div wire:key="pet-{{ $pet->id }}" class="{{ $row }}">
                            <span class="{{ $label }}">{{ $pet->species }}</span>
                            <span class="{{ $value }}">{{ collect([$pet->name, $pet->size])->filter()->implode(' · ') ?: '—' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.card>

        <x-admin.card heading="Candidature partner">
            @if ($applications->isEmpty())
                <x-admin.empty>Nessuna candidatura.</x-admin.empty>
            @else
                <div class="px-5 py-3">
                    @foreach ($applications as $application)
                        <div wire:key="application-{{ $application->id }}" class="{{ $row }} items-center">
                            <span class="{{ $label }}">{{ $date($application->created_at) }}</span>
                            <span class="{{ $value }}">{{ $application->business_name }} <span class="font-normal text-gray-600">· {{ $application->offer_type }}</span></span>
                            @switch($application->status)
                                @case(PartnerApplication::STATUS_REGISTERED)
                                    <x-admin.badge tone="success">Registrato</x-admin.badge>
                                    @break
                                @case(PartnerApplication::STATUS_INVITED)
                                    <x-admin.badge tone="info">Invitato</x-admin.badge>
                                    @break
                                @default
                                    <x-admin.badge tone="warning">Da invitare</x-admin.badge>
                            @endswitch
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.card>
    </div>

    @include('livewire.admin.people.partials.anonymize-modal')
</div>
