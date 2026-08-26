{{-- Contattaci: form sullo stile "Lavora con noi" + colonna dati di contatto (unifica i vecchi link Contattaci/Assistenza) --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    // Stessi override campo del form "Lavora con noi" (box r3 bordo #C8C8C8 su desktop, r5 h45 su mobile).
    $inputClass = '[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555] max-lg:[&_input]:!h-[45px] max-lg:[&_input]:!rounded-[5px] max-lg:[&_input]:!text-sm max-lg:[&_input]:!text-[#0D171A] max-lg:[&_input]:placeholder:!not-italic max-lg:[&_input]:placeholder:!text-[#959595]';
    $selectClass = '!border-[#C8C8C8] max-lg:!h-[45px] max-lg:!rounded-[5px] max-lg:!text-sm max-lg:!text-[#0D171A]';
    $labelClass = '!text-xs !text-gray-600 max-lg:!text-[#555555]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    {{-- Banda gradiente come "Lavora con noi" (296deg #FF3EA526 → #68CDEB33) --}}
    <main class="flex-1 bg-[linear-gradient(296deg,#FF3EA526_0%,#68CDEB33_100%)]">
        {{-- Hero: mappa Google della sede (stessa componente dei detail; senza chiave la banda sparisce) --}}
        @if (filled(config('services.google.maps_key')))
            <section class="relative h-[380px] w-full overflow-hidden max-lg:h-[200px]">
                <x-google-map query="Via Cattani 11, Contà (Trento), Italia" :alt="__('contact.map_alt')" class="h-full" />
                <span class="pointer-events-none absolute left-1/2 top-[260px] max-lg:top-[130px] inline-flex h-[38px] -translate-x-1/2 items-center gap-2 whitespace-nowrap rounded-full bg-brand-yellow px-[18px] text-[13px] font-semibold text-black">
                    <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                    Animal Amo Srl
                </span>
            </section>
        @endif
        <div class="{{ $px }} pt-[60px] pb-20 max-lg:pt-[54px] max-lg:pb-10">
            <h1 class="text-4xl font-bold text-black max-lg:text-[18px] max-lg:leading-[21px] max-lg:text-[#0D171A]">{{ __('contact.heading') }}</h1>
            <p class="mt-4 max-w-4xl text-lg text-black max-lg:mt-[10px] max-lg:text-[15px] max-lg:leading-5 max-lg:text-ink-700">{{ __('contact.intro') }}</p>

            <div class="mt-10 flex items-start gap-6 max-lg:mt-7 max-lg:flex-col">
                {{-- Card form (stessa card del form partner; su mobile campi a tutta pagina) --}}
                <form wire:submit="submit"
                    class="min-w-0 flex-1 rounded-[3px] border border-gray-150 bg-white/50 px-6 pb-8 pt-8 max-lg:w-full max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:p-0 max-lg:[&_[data-flux-label]]:!mb-1">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:field>
                            <flux:label class="{{ $labelClass }}">{{ __('contact.first_name') }}</flux:label>
                            <flux:input wire:model="form.firstName" placeholder="{{ __('contact.first_name') }}" class="{{ $inputClass }}" />
                            <flux:error name="form.firstName" class="!mt-1 !text-xs" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="{{ $labelClass }}">{{ __('contact.last_name') }}</flux:label>
                            <flux:input wire:model="form.lastName" placeholder="{{ __('contact.last_name') }}" class="{{ $inputClass }}" />
                            <flux:error name="form.lastName" class="!mt-1 !text-xs" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="{{ $labelClass }}">{{ __('contact.email') }}</flux:label>
                            <flux:input type="email" wire:model="form.email" placeholder="{{ __('contact.email') }}" class="{{ $inputClass }}" />
                            <flux:error name="form.email" class="!mt-1 !text-xs" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="{{ $labelClass }}">{{ __('contact.reason') }}</flux:label>
                            <flux:select wire:model="form.reason" placeholder="{{ __('contact.select_placeholder') }}" class="{{ $selectClass }}">
                                <flux:select.option>{{ __('contact.reason_info') }}</flux:select.option>
                                <flux:select.option>{{ __('contact.reason_support') }}</flux:select.option>
                                <flux:select.option>{{ __('contact.reason_partner') }}</flux:select.option>
                                <flux:select.option>{{ __('contact.reason_other') }}</flux:select.option>
                            </flux:select>
                            <flux:error name="form.reason" class="!mt-1 !text-xs" />
                        </flux:field>
                    </div>

                    <flux:field class="mt-4">
                        <flux:label class="{{ $labelClass }}">{{ __('contact.message') }}</flux:label>
                        <flux:textarea wire:model="form.message" rows="4" placeholder="{{ __('contact.message_placeholder') }}" class="!h-[117px] !border-[#C8C8C8] placeholder:!italic placeholder:!text-[#555555] max-lg:!h-[219px] max-lg:!rounded-[5px] max-lg:!text-sm max-lg:!text-[#0D171A] max-lg:placeholder:!not-italic max-lg:placeholder:!text-[#959595]" />
                        <flux:error name="form.message" class="!mt-1 !text-xs" />
                    </flux:field>

                    <div class="mt-8 flex justify-end max-lg:justify-center">
                        <flux:button type="submit" class="!rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white max-lg:!h-[39px] max-lg:!w-[327px]">{{ __('contact.submit') }}</flux:button>
                    </div>
                </form>

                {{-- Colonna dati di contatto (email informazioni, Instagram, dati societari); su mobile senza card, come i campi del form --}}
                <aside class="w-[400px] shrink-0 rounded-[3px] border border-gray-150 bg-white/50 px-6 py-8 max-lg:w-full max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:p-0">
                    <h2 class="text-lg font-bold text-black">{{ __('contact.info_heading') }}</h2>

                    <div class="mt-6 flex items-start gap-3.5">
                        <flux:icon.envelope class="mt-0.5 h-5 w-5 shrink-0 text-[#0D171A]" />
                        <div>
                            <p class="text-[15px] font-medium text-[#0D171A]">{{ __('contact.info_email_title') }}</p>
                            <a href="mailto:animalamo24@gmail.com" class="mt-1 block text-[15px] text-[#555555] hover:text-brand-cyan">animalamo24@gmail.com</a>
                        </div>
                    </div>

                    <div class="mt-6 flex items-start gap-3.5">
                        <flux:icon.instagram class="mt-0.5 h-5 w-auto shrink-0 text-[#0D171A]" />
                        <div>
                            <p class="text-[15px] font-medium text-[#0D171A]">{{ __('contact.info_instagram_title') }}</p>
                            <a href="https://www.instagram.com/animal___amo" target="_blank" rel="noopener" class="mt-1 block text-[15px] text-[#555555] hover:text-brand-cyan">@animal___amo</a>
                        </div>
                    </div>

                    <flux:separator class="mt-6" />

                    {{-- Dati societari (non tradotti: ragione sociale e indirizzo) --}}
                    <div class="mt-6 flex items-start gap-3.5">
                        <flux:icon.pin class="mt-1 h-[15px] w-3 shrink-0 text-[#0D171A]" />
                        <div class="text-[15px] leading-[22px]">
                            <p class="font-medium text-[#0D171A]">Animal Amo Srl</p>
                            <p class="mt-1 text-[#555555]">P.IVA 02746270228</p>
                            <p class="text-[#555555]">Via Cattani 11 — Contà (Trento)</p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    {{-- Conferma "Grazie!" in-place su entrambi i viewport (stessa sweet-alert del form partner) --}}
    <x-sweet-alert name="contact-message-sent" model="showConfirmation" wire:close="closeConfirmation"
        :heading="__('contact.thanks_heading')" :close-label="__('nav.close')">
        {{ __('contact.thanks_line_1') }}<br>{{ __('contact.thanks_line_2') }}
    </x-sweet-alert>

    {{-- Su mobile il footer lascia il posto alla tabbar (come "Lavora con noi") --}}
    <div class="max-lg:hidden">
        @include('partials.footer-minimal')
    </div>
    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>
</div>
