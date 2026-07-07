{{-- Profilo – sicurezza (XD): cornice Profilo, card "Sicurezza e Privacy" con password + link privacy --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A]';
    $inputClasses = '!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px]">
            <div class="flex flex-col gap-10 lg:flex-row lg:gap-[61px]">
                @include('partials.profile-sidebar', ['active' => 'sicurezza'])

                <section class="w-full min-w-0 p-6 lg:w-[1012px] lg:shrink-0 {{ $card }}">
                    <h1 class="text-2xl font-bold leading-none text-black">Sicurezza e Privacy</h1>

                    {{-- Password + conferma affiancate (472 + gap 20) --}}
                    <div class="mt-6 flex flex-col gap-4 min-[68rem]:flex-row min-[68rem]:gap-5">
                        @foreach ([
                            ['model' => 'password', 'label' => 'Password'],
                            ['model' => 'passwordConfirm', 'label' => 'Conferma Password'],
                        ] as $field)
                            <div class="w-full min-[68rem]:w-[472px]" wire:key="field-{{ $field['model'] }}">
                                <flux:label class="!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555]">{{ $field['label'] }}</flux:label>
                                <div class="mt-[11px]">
                                    {{-- Asterischi XD resi come placeholder (un valore precompilato verrebbe inviato) --}}
                                    <flux:input type="password" wire:model="{{ $field['model'] }}" placeholder="***************" class="{{ $inputClasses }} [&_input]:placeholder:!text-[#0D171A]" />
                                </div>
                                <flux:error name="{{ $field['model'] }}" class="!mt-1 !pl-[15px] !text-xs" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Link e testi privacy come da XD (y321..533): magenta 13px, lorem 15px, elimina #EA2E68 --}}
                    {{-- TODO: flussi reimposta password / impostazioni privacy / elimina account — flussi non presenti nel design, da segnalare al cliente --}}
                    <a href="#" class="mt-4 block text-[13px] leading-none text-brand-magenta">Reimposta password</a>

                    <p class="mt-[30px] max-w-[964px] text-[15px] leading-[21px] text-[#0D171A]">{{ $privacyPlaceholder }}</p>

                    <a href="#" class="mt-[14px] block text-[13px] leading-none text-brand-magenta">Impostazioni sulla privacy</a>

                    <p class="mt-[29px] max-w-[964px] text-[15px] leading-[21px] text-[#0D171A]">{{ $privacyPlaceholder }}</p>

                    <a href="#" class="mt-4 block text-[13px] leading-none text-[#EA2E68]">Elimina account</a>

                    <flux:button wire:click="save" class="mt-[37px] !h-10 !w-[103px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan">Salva</flux:button>
                </section>
            </div>
        </div>
    </main>

    @include('partials.footer-minimal')
</div>
