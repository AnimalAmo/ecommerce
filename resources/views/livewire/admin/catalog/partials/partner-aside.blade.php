{{-- Colonna laterale condivisa dalle tre pagine "Nuova scheda": chi è il
     partner, cosa succederà alla scheda, e il bottone che la crea.

     Variabili attese nella render() del componente (le fornisce
     CreatesPartnerService::partnerViewData()):
       $eligible → Collection<int, string> id => ragione sociale
       $summary  → array|null da partnerSummary()
     Più le proprietà pubbliche del trait: $partnerId, $partnerChoice,
     $pendingNotice. --}}
@php $t = 'admin-catalog.create.'; @endphp

<div class="flex min-w-0 flex-col gap-3.5">
    <x-admin.card :heading="__($t.'partner_label')">
        <div class="flex flex-col gap-4 p-5">
            {{-- `label` come prop: così Flux inietta da sé il proprio
                 flux:error sotto il campo. $partnerId è #[Locked]: la scelta
                 passa da $partnerChoice → updatedPartnerChoice(), che
                 ricontrolla l'idoneità sul server. --}}
            <flux:select
                variant="listbox"
                searchable
                wire:model.live="partnerChoice"
                :label="__($t.'partner_label')"
                :description="__($t.'partner_help')"
                :placeholder="__($t.'partner_placeholder')"
            >
                @foreach ($eligible as $id => $label)
                    <flux:select.option value="{{ $id }}" wire:key="partner-{{ $id }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($summary)
                <div class="flex flex-col gap-2 border-t border-gray-150 pt-4">
                    <p class="m-0 text-[14.5px] font-bold text-admin-rail">{{ $summary['business_name'] ?: '—' }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- La chiave vera è admin-people.users.payment_mode.*
                             (payment_modes non esiste: darebbe la chiave grezza). --}}
                        <x-admin.badge :tone="$summary['payment_mode'] === 'online' ? 'info' : 'muted'">
                            {{ __($t.'mode_label') }}: {{ __('admin-people.users.payment_mode.'.$summary['payment_mode']) }}
                        </x-admin.badge>
                        {{-- Lo stato Stripe vero (payable|incomplete|none), non
                             solo il sì/no di can_publish: la spec §5.3 chiede
                             un badge sulla modalità E uno su Stripe. --}}
                        <x-admin.badge :tone="$summary['stripe_status'] === 'payable' ? 'success' : ($summary['stripe_status'] === 'incomplete' ? 'warning' : 'muted')">
                            {{ __($t.'stripe_label') }}: {{ __($t.'stripe_status.'.$summary['stripe_status']) }}
                        </x-admin.badge>
                    </div>
                    <p class="m-0 text-[13px] text-gray-600">{{ __($t.'listings_count', ['count' => $summary['listings']]) }}</p>
                </div>
            @endif
        </div>
    </x-admin.card>

    {{-- Avviso PRIMA di compilare undici sezioni: un partner online senza
         Stripe si può scegliere, ma la scheda nascerà in attesa. --}}
    @if ($summary && ! $summary['can_publish'])
        <x-admin.notice tone="warning" :heading="__($t.'awaiting_stripe_heading')">
            {{ __($t.'stripe_missing_notice') }}
        </x-admin.notice>
    @endif

    @if (config('admin.moderation'))
        <x-admin.notice tone="info" :heading="__('admin-catalog.approvals.on_heading')">
            {{ __($t.'moderation_note') }}
        </x-admin.notice>
    @endif

    {{-- Esito "in attesa": il layout del pannello non ha un canale flash, e un
         redirect si porterebbe via il toast. L'avviso resta in pagina, con i
         due link che il Task 2 ha dichiarato. --}}
    @if ($pendingNotice !== null)
        <x-admin.notice tone="warning" :heading="__($t.'awaiting_stripe_heading')">
            {{ $pendingNotice }}
            <x-slot:actions>
                @if ($partnerId !== null)
                    <x-admin.button tone="primary" :href="route('admin.users.show', $partnerId)" wire:navigate>{{ __($t.'awaiting_open_partner') }}</x-admin.button>
                @endif
                <x-admin.button :href="route('admin.catalog.index')" wire:navigate>{{ __($t.'awaiting_back') }}</x-admin.button>
            </x-slot:actions>
        </x-admin.notice>
    @endif

    {{-- `$created` spento dopo un salvataggio riuscito: sul percorso "in attesa"
         la pagina non fa redirect e il bottone resterebbe cliccabile. --}}
    <x-admin.button tone="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save" :disabled="$created" class="self-start">
        {{ __($t.'submit') }}
    </x-admin.button>
</div>
