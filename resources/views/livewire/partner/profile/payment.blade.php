{{-- Profilo partner – Metodo di pagamento (XD "Profilo – metodo di pagamento") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php $fieldClass = '[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8]'; @endphp
@php $labelClass = '!text-xs !font-normal !text-[#555555]'; @endphp
@php $darkButton = '!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-10 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]'; @endphp

<div class="flex min-h-screen flex-col bg-[linear-gradient(296deg,#FF3EA51A_0%,#68CDEB1A_100%)] font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <div class="flex flex-col gap-8 md:flex-row">

                @include('partials.partner-profile-sidebar')

                <div class="hidden w-px self-stretch bg-white md:block"></div>

                <div class="flex-1 rounded-[10px] bg-white p-6">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.profile.payment_heading') }}</h1>

                    {{-- Come ricevi i pagamenti (richiesta della cliente, 22/09/2026): online su AnimalAmo,
                         oppure direttamente al partner. "Online" senza Stripe operativo è disabilitato qui e
                         rifiutato anche dal service: il disabled è solo grafica. --}}
                    <form wire:submit="savePaymentMode" class="mt-6 rounded-[10px] border border-[#C8C8C8] p-5">
                        {{-- Titolo e aiuto come label/description del gruppo: danno il nome alle radio
                             per i lettori di schermo, e Flux mette da sé l'errore dentro il campo. --}}
                        <flux:radio.group
                            wire:model="paymentMode"
                            :label="__('partner.payment_mode.section')"
                            :description="__('partner.payment_mode.help')"
                            label:class="!text-base !font-bold !text-[#0D171A]"
                            description:class="!mb-4 !text-sm !text-[#555555]"
                            class="[--color-accent:#68CDEB]"
                        >
                            <flux:radio value="online" :label="__('partner.payment_mode.online')" :disabled="$onlineLocked" />
                            <flux:radio value="on_site" :label="__('partner.payment_mode.on_site')" />
                        </flux:radio.group>

                        @if ($onlineLocked)
                            <p class="mt-2 text-sm text-[#555555]">{{ __('partner.payment_mode.online_needs_stripe') }}</p>
                        @endif

                        <div class="mt-5 md:max-w-[480px]">
                            <flux:input
                                wire:model="paymentUrl"
                                :label="__('partner.payment_mode.url_label')"
                                :description="__('partner.payment_mode.url_help')"
                                placeholder="https://"
                                class="{{ $fieldClass }}"
                            />
                        </div>

                        <div class="mt-5 flex justify-end">
                            <flux:button type="submit" class="{{ $darkButton }}">{{ __('partner.payment_mode.save') }}</flux:button>
                        </div>
                    </form>

                    {{-- Collegamento del conto Stripe: obbligatorio per chi si fa pagare online. Chi si fa
                         pagare direttamente può prepararlo in anticipo per passare online. --}}
                    <div class="mt-6 rounded-[10px] border border-[#C8C8C8] p-5">
                        @if ($stripeConnected)
                            <div class="flex items-center gap-3">
                                <flux:badge color="green">{{ __('partner.profile.stripe.connected') }}</flux:badge>
                                <p class="text-sm text-[#555555]">{{ __('partner.profile.stripe.connected_help') }}</p>
                            </div>
                        @else
                            <p class="text-base font-bold text-[#0D171A]">
                                {{ $stripeStarted ? __('partner.profile.stripe.incomplete') : __('partner.profile.stripe.disconnected') }}
                            </p>
                            <p class="mt-2 text-sm text-[#555555]">{{ $paysOnSite ? __('partner.profile.stripe.help_on_site') : __('partner.profile.stripe.help') }}</p>

                            @if ($stripeRequirements !== [])
                                <ul class="mt-3 list-disc pl-5 text-sm text-[#555555]">
                                    @foreach ($stripeRequirements as $requirement)
                                        <li>{{ $requirement }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <flux:button
                                wire:click="connectStripe"
                                class="mt-4 {{ $darkButton }} [&>span]:flex [&>span]:items-center [&>span]:gap-2"
                            >{{ $stripeStarted ? __('partner.profile.stripe.resume') : __('partner.profile.stripe.connect') }}</flux:button>
                        @endif
                    </div>

                    <form wire:submit="save" class="mt-8">
                        <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-3">
                            {{-- items-start: senza, l'errore di un campo stira tutta la riga. --}}
                            <div class="grid grid-cols-1 items-start gap-x-4 gap-y-5 md:col-span-2 md:grid-cols-2">
                                {{-- Campi composti a mano: Flux non inietta l'errore, flux:error va messo a mano. --}}
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.account_holder') }} *</flux:label>
                                    <flux:input wire:model="form.accountHolder" class="{{ $fieldClass }}" />
                                    <flux:error name="form.accountHolder" />
                                </flux:field>
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.iban') }} *</flux:label>
                                    <flux:input wire:model="form.iban" class="{{ $fieldClass }}" />
                                    <flux:error name="form.iban" />
                                </flux:field>
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.bic') }} *</flux:label>
                                    <flux:input wire:model="form.bic" class="{{ $fieldClass }}" />
                                    <flux:error name="form.bic" />
                                </flux:field>

                                <div class="flex justify-end md:col-span-2">
                                    <flux:button type="submit" class="{{ $darkButton }}">{{ __('partner.profile.save') }}</flux:button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
