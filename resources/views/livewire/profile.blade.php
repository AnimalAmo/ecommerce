{{-- Profilo (XD "Profilo"): sfondo sfumato magenta→ciano al 10%, sidebar voci profilo + card "Informazioni personali" --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A]';
    // Input 472x40 stile checkout, senza spunta (i campi profilo non la prevedono)
    $inputClasses = '!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    {{-- Gradiente XD "Rettangolo 783": #FF3EA5→#68CDEB entrambi al 10%, dal basso-destra verso l'alto-sinistra --}}
    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px]">
            <div class="flex flex-col gap-10 lg:flex-row lg:gap-[61px]">
                @include('partials.profile-sidebar', ['active' => 'profilo'])

                {{-- Card "Informazioni personali" 1012x555: due colonne di campi 472 (gap 20), righe a passo 79 --}}
                <section class="w-full min-w-0 p-6 lg:w-[1012px] lg:shrink-0 {{ $card }}">
                    <h1 class="text-2xl font-bold leading-none text-black">Informazioni personali</h1>

                    <div class="mt-6 flex flex-col gap-4 min-[68rem]:flex-row min-[68rem]:gap-5">
                        @foreach (['left' => $fieldsLeft, 'right' => $fieldsRight] as $col => $fields)
                            <div class="w-full space-y-4 min-[68rem]:w-[472px]" wire:key="col-{{ $col }}">
                                @foreach ($fields as $model => $label)
                                    <div wire:key="field-{{ $model }}">
                                        <flux:label class="!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555]">{{ $label }}</flux:label>
                                        <div class="mt-[11px]">
                                            <flux:input type="text" wire:model="{{ $model }}" class="{{ $inputClasses }}" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    {{-- TODO: salvataggio profilo backend — per ora il template è statico --}}
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
