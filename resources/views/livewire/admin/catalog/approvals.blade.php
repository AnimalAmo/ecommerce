<div class="flex flex-col gap-[18px]">
    <x-admin.page-header
        heading="Schede da approvare"
        :sub="match (true) {
            $cards->isEmpty() => 'Nessuna scheda in attesa.',
            $cards->count() === 1 => 'Una scheda è in attesa. Finché non la approvi non si vede sul sito.',
            default => $cards->count().' schede sono in attesa. Finché non le approvi non si vedono sul sito.',
        }"
    />

    @if ($moderation)
        <x-admin.notice heading="L'approvazione è attiva">
            Le nuove schede dei partner passano da qui prima di andare online: conviene rispondere entro un paio di giorni.
            Quelle già approvate restano online anche quando il partner le modifica.
        </x-admin.notice>
    @else
        <x-admin.notice heading="L'approvazione preventiva è spenta">
            Oggi i partner pubblicano da soli: le nuove schede vanno online appena chiudono il percorso di inserimento.
            Si accende con <code>ADMIN_MODERATION=true</code>, dopo averlo annunciato ai partner.
        </x-admin.notice>
    @endif

    <div class="flex flex-col gap-3.5">
        @forelse ($cards as $card)
            <x-admin.card wire:key="approval-{{ $card['key'] }}" class="flex flex-wrap gap-[18px] px-5 py-[18px]">
                <span aria-hidden="true" class="h-[110px] w-[148px] shrink-0 rounded-lg bg-brand-cyan-bg bg-cover bg-center" @if ($card['img']) style="background-image: url('{{ $card['img'] }}')" @endif></span>
                <div class="min-w-[220px] flex-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <x-admin.badge :tone="$card['typeTone']">{{ $card['type'] }}</x-admin.badge>
                        <span class="text-[12.5px] text-gray-400">inviata {{ $card['when'] }}</span>
                    </div>
                    <h2 class="mt-[9px] text-lg font-bold text-admin-rail">{{ $card['name'] }}</h2>
                    <p class="mt-1.5 flex items-center gap-[5px] text-[13px] text-gray-400">
                        <flux:icon.pin class="size-[13px] shrink-0" />{{ $card['place'] }} · {{ $card['partner'] }}
                    </p>
                    <p class="mt-3 text-sm leading-relaxed text-ink-800">{{ $card['description'] }}</p>
                    <div class="mt-3.5 flex flex-wrap gap-x-5 gap-y-2">
                        @foreach ($card['facts'] as $fact)
                            <span class="text-[13px] text-gray-600"><span class="text-gray-400">{{ $fact['label'] }}:</span> {{ $fact['value'] }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="flex min-w-[190px] shrink-0 flex-col gap-[9px]">
                    <x-admin.button tone="primary" icon="check" class="!justify-center" wire:click="approve('{{ $card['family'] }}', {{ $card['id'] }})">Approva e pubblica</x-admin.button>
                    <x-admin.button icon="x-mark" class="!justify-center" wire:click="askChanges('{{ $card['family'] }}', {{ $card['id'] }})">Chiedi modifiche</x-admin.button>
                    <x-admin.button tone="ghost" icon="eye" class="!justify-center" :href="$card['url']" wire:navigate>Vedi la scheda</x-admin.button>
                </div>
            </x-admin.card>
        @empty
            <x-admin.card>
                <x-admin.empty>Niente da approvare: tutte le schede dei partner sono già state valutate.</x-admin.empty>
            </x-admin.card>
        @endforelse
    </div>

    <flux:modal name="request-changes" class="w-full max-w-[520px]">
        @if ($changing)
            <form wire:submit="requestChanges" class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">Chiedere modifiche?</flux:heading>
                    <p class="mt-3 text-[14.5px] leading-normal text-gray-600">«{{ $changing['name'] }}» resta fuori dal sito. Il partner riceve questo messaggio per mail e torna in attesa quando ripubblica.</p>
                </div>
                <flux:textarea wire:model="note" label="Cosa va cambiato" rows="5" placeholder="Per esempio: le foto sono sfocate, e manca il supplemento per l'animale." />
                <div class="flex flex-wrap justify-end gap-2.5">
                    <flux:modal.close>
                        <x-admin.button>Annulla</x-admin.button>
                    </flux:modal.close>
                    <x-admin.button tone="primary" type="submit" icon="paper-airplane">Invia al partner</x-admin.button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
