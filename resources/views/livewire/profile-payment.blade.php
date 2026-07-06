{{-- Profilo – metodo pagamento (XD): stessa cornice del Profilo, card "Informazioni del metodo di pagamento" 1012x397 --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A]';
    $inputClasses = '!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input::placeholder]:!text-[#959595]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px]">
            <div class="flex flex-col gap-10 lg:flex-row lg:gap-[61px]">
                @include('partials.profile-sidebar', ['active' => 'pagamento'])

                <section class="w-full min-w-0 p-6 lg:w-[1012px] lg:shrink-0 {{ $card }}">
                    <h1 class="text-2xl font-bold leading-none text-black">Informazioni del metodo di pagamento</h1>

                    <div class="mt-6 space-y-4">
                        {{-- Riga 1: titolare + numero carta affiancati (472 + gap 20) --}}
                        <div class="flex flex-col gap-4 min-[68rem]:flex-row min-[68rem]:gap-5">
                            @foreach ([
                                ['model' => 'cardHolder', 'label' => 'Titolare carta'],
                                ['model' => 'cardNumber', 'label' => 'Numero della carta'],
                            ] as $field)
                                <div class="w-full min-[68rem]:w-[472px]" wire:key="field-{{ $field['model'] }}">
                                    <flux:label class="!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555]">{{ $field['label'] }}</flux:label>
                                    <div class="mt-[11px]">
                                        <flux:input type="text" wire:model="{{ $field['model'] }}" class="{{ $inputClasses }}" />
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Righe 2-3: scadenza e cvv da 295px, vuoti con placeholder come in XD --}}
                        @foreach ([
                            ['model' => 'cardExpiry', 'label' => 'Data di scadenza', 'placeholder' => 'MM/AA'],
                            ['model' => 'cardCvv', 'label' => 'Codice di sicurezza', 'placeholder' => '3 cifre'],
                        ] as $field)
                            <div class="w-full min-[68rem]:w-[295px]" wire:key="field-{{ $field['model'] }}">
                                <flux:label class="!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555]">{{ $field['label'] }}</flux:label>
                                <div class="mt-[11px]">
                                    <flux:input type="text" wire:model="{{ $field['model'] }}" placeholder="{{ $field['placeholder'] }}" class="{{ $inputClasses }}" />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- TODO: salvataggio metodo di pagamento backend — per ora il template è statico --}}
                    <flux:button class="mt-8 !h-10 !w-[103px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan">Salva</flux:button>
                </section>
            </div>
        </div>
    </main>

    @include('partials.footer-minimal')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />
</div>
