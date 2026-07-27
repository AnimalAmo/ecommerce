{{-- Input telefono con prefisso internazionale (spec: docs/specs/2026-07-27-phone-input-design.md).
     Alpine tiene prefisso e numero separati, la property Livewire riceve sempre l'E.164.
     Il wrapper è wire:ignore: il sottoalbero è di Alpine, il morph di Livewire non deve toccarlo. --}}
@props([
    'model',
    'value' => null,
    'inputClass' => '',
    'selectClass' => '',
    // NON chiamarlo "placeholder": flux:select lo eredita dallo scope (@blaze unsafe)
    // e genera un'opzione vuota selezionata al posto del prefisso.
    'numberPlaceholder' => null,
    // Il checkout mostra la spunta sui campi pieni: lì il numero va sincronizzato subito.
    'live' => false,
])

@php
    $parts = \App\Support\Phone::split($value);
@endphp

<div
    wire:ignore
    wire:key="phone-input-{{ \Illuminate\Support\Str::slug($model) }}"
    x-data="{
        country: @js($parts['country']),
        national: @js($parts['national']),
        dials: @js(\App\Support\Phone::dialCodes()),
        last: @js((string) $value),
        commit: null,
        sync() {
            const typed = this.national.trim();

            // Numero incollato già internazionale: si tiene com'è, ci pensa la validazione.
            // Gli zeri iniziali non si tolgono: nei fissi italiani (+39 06 …) sono significativi.
            const value = typed === ''
                ? ''
                : typed.startsWith('+')
                    ? typed.replace(/[^\d+]/g, '')
                    : '+' + this.dials[this.country] + typed.replace(/\D/g, '');

            if (value === this.last) return;

            this.last = value;
            // Sempre differito: un set 'live' farebbe una richiesta per tasto premuto.
            $wire.set(@js($model), value, false);
            @if ($live)
                // Il render che accende la spunta arriva a fine digitazione, non a ogni cifra.
                clearTimeout(this.commit);
                this.commit = setTimeout(() => $wire.$commit(), 400);
            @endif
        },
    }"
    x-effect="sync()"
    class="flex items-start gap-2"
>
    {{-- appearance-auto: fuori da flux:with-field il select default perde la freccia
         (Flux la disegna nel wrapper del field), quindi si riprende quella nativa. --}}
    <flux:select x-model="country" class="!w-auto shrink-0 !appearance-auto !ps-2 !pe-1 {{ $selectClass }}">
        @foreach (\App\Support\Phone::countries() as $country)
            <flux:select.option value="{{ $country['code'] }}" title="{{ $country['name'] }}">{{ $country['flag'] }} {{ $country['code'] }} +{{ $country['dial'] }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:input
        type="tel"
        x-model="national"
        inputmode="tel"
        autocomplete="tel-national"
        :placeholder="$numberPlaceholder"
        class="min-w-0 flex-1 {{ $inputClass }}"
    />
</div>
