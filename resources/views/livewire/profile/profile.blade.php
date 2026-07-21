{{-- Profilo (XD "Profilo"): sfondo sfumato magenta→ciano al 10%, sidebar voci profilo + card "Informazioni personali" --}}
@php
    // Mobile: /profilo mostra solo il menu, i campi vivono su /profilo/dati-anagrafici
    // (due artboard distinti nell'app). Desktop: entrambe le rotte rendono sidebar + card.
    $isPersonalData = request()->routeIs('profilo.anagrafica');

    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    // Su mobile (XD app) la card sparisce: i campi stanno direttamente sullo sfondo
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A] max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:shadow-none';
    // Input 472x40 stile checkout, senza spunta (i campi profilo non la prevedono); app: r5, testo 14
    $inputClasses = '!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 max-lg:[&_input]:!rounded-[5px] max-lg:[&_input]:!px-4 max-lg:[&_input]:!text-sm';
    $labelClasses = '!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555] max-lg:!pl-[7px]';
    // Ordine dei campi dell'artboard app "Profilo – dati anagrafici": il cellulare risale
    // in quinta posizione e la tipologia animale scende in fondo (classi letterali: Tailwind le deve vedere)
    $mobileOrder = [
        'firstName' => 'max-lg:order-1',
        'lastName' => 'max-lg:order-2',
        'birthDate' => 'max-lg:order-3',
        'email' => 'max-lg:order-4',
        'phone' => 'max-lg:order-5',
        'address' => 'max-lg:order-6',
        'city' => 'max-lg:order-7',
        'zip' => 'max-lg:order-8',
        'petType' => 'max-lg:order-9',
    ];
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    {{-- Gradiente XD "Rettangolo 783": #FF3EA5→#68CDEB entrambi al 10%, dal basso-destra verso l'alto-sinistra --}}
    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        {{-- App: il menu parte dal titolo a y141, la scheda anagrafica dalla freccia a y123 --}}
        <div class="{{ $px }} pb-[140px] pt-[60px] max-lg:pb-8 {{ $isPersonalData ? 'max-lg:pt-9' : 'max-lg:pt-[54px]' }}">
            <div class="flex flex-col gap-10 lg:flex-row lg:gap-[61px] max-lg:gap-8">
                @include('partials.profile-sidebar', ['active' => 'profilo'])

                {{-- Card "Informazioni personali" 1012x555: due colonne di campi 472 (gap 20), righe a passo 79 --}}
                <section class="w-full min-w-0 p-6 lg:w-[1012px] lg:shrink-0 max-lg:p-0 {{ $card }} {{ $isPersonalData ? '' : 'max-lg:hidden' }}">
                    {{-- App: la scheda si chiama "Dati anagrafici", con la freccia indietro verso il menu --}}
                    @if ($isPersonalData)
                        @include('partials.profile-mobile-header', ['title' => __('profile.nav_personal_data')])
                    @endif

                    <h1 class="text-2xl font-bold leading-none text-black max-lg:hidden">{{ __('profile.personal_info_title') }}</h1>

                    <div class="mt-6 flex flex-col gap-4 min-[68rem]:flex-row min-[68rem]:gap-5 max-lg:mt-[10px] max-lg:gap-[21px]">
                        @foreach (['left' => $fieldsLeft, 'right' => $fieldsRight] as $col => $fields)
                            {{-- contents su mobile: i campi diventano figli diretti della colonna e si riordinano come nell'app --}}
                            <div class="w-full space-y-4 min-[68rem]:w-[472px] max-lg:contents max-lg:space-y-0" wire:key="col-{{ $col }}">
                                @foreach ($fields as $model => $label)
                                    <div wire:key="field-{{ $model }}" class="{{ $mobileOrder[$model] }}">
                                        <flux:label class="{{ $labelClasses }}">{{ $label }}</flux:label>
                                        <div class="mt-[11px] max-lg:mt-1">
                                            <flux:input type="text" wire:model="{{ $model }}" class="{{ $inputClasses }}" />
                                        </div>
                                        <flux:error name="{{ $model }}" class="!mt-1 !pl-[15px] !text-xs max-lg:!pl-[7px]" />
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    {{-- Ri-autenticazione: obbligatoria solo se si modifica l'email --}}
                    @if ($email !== auth()->user()->email)
                        <div class="mt-6 w-full min-[68rem]:w-[472px] max-lg:mt-[21px]" wire:key="field-currentPassword">
                            <flux:label class="{{ $labelClasses }}">{{ __('profile.field_current_password_email') }}</flux:label>
                            <div class="mt-[11px] max-lg:mt-1">
                                <flux:input type="password" wire:model="currentPassword" placeholder="***************" class="{{ $inputClasses }} [&_input]:placeholder:!text-[#0D171A]" />
                            </div>
                            <flux:error name="currentPassword" class="!mt-1 !pl-[15px] !text-xs max-lg:!pl-[7px]" />
                        </div>
                    @endif

                    {{-- App: bottone azzurro a tutta larghezza (343x39) --}}
                    <flux:button wire:click="save" class="mt-8 !h-10 !w-[103px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan max-lg:!h-[39px] max-lg:!w-full max-lg:!font-semibold">{{ __('profile.save') }}</flux:button>
                </section>
            </div>
        </div>
    </main>

    <div class="max-lg:hidden">
        @include('partials.footer-minimal')
    </div>

    {{-- Tabbar mobile: il footer minimal non la include --}}
    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>
</div>
