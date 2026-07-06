{{-- Lavora con noi (XD: "Lavora con noi – inserimento campi") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    {{-- Banda gradiente (XD: 296deg #FF3EA526 → #68CDEB33) --}}
    <main class="flex-1 bg-[linear-gradient(296deg,#FF3EA526_0%,#68CDEB33_100%)]">
        <div class="{{ $px }} pt-[60px] pb-20">
            <h1 class="text-4xl font-bold text-black">Iscrivi la tua struttura o un’attività pet friendly</h1>
            <p class="mt-4 max-w-4xl text-lg text-black">Siamo sempre aperti ad ampliare le possibilità per le persone che scelgono Animal-amo. Proponi la tua realtà e collabora con noi!</p>

            {{-- Card form (XD: 1496x512, bianco op 0.5, bordo #E9E9E9, r 3) --}}
            <form wire:submit="submit" class="mt-10 rounded-[3px] border border-gray-150 bg-white/50 px-6 pb-8 pt-8">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">Nome</flux:label>
                        <flux:input wire:model="form.firstName" placeholder="Nome" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">Cognome</flux:label>
                        <flux:input wire:model="form.lastName" placeholder="Cognome" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">Email</flux:label>
                        <flux:input type="email" wire:model="form.email" placeholder="Email" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">Cellulare</flux:label>
                        <flux:input type="tel" wire:model="form.phone" placeholder="Cellulare" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">Sito web</flux:label>
                        <flux:input wire:model="form.website" placeholder="Sito web" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">Città</flux:label>
                        <flux:input wire:model="form.city" placeholder="Città" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">Nome attività</flux:label>
                        <flux:input wire:model="form.businessName" placeholder="Nome attività" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">Il tuo ruolo</flux:label>
                        <flux:select wire:model="form.role" placeholder="Il tuo ruolo" class="!border-[#C8C8C8] [&_select]:!border-[#C8C8C8]">
                            <flux:select.option>Proprietario</flux:select.option>
                            <flux:select.option>Gestore</flux:select.option>
                            <flux:select.option>Dipendente</flux:select.option>
                            <flux:select.option>Altro</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">Tipologia Offerta</flux:label>
                        <flux:select wire:model="form.offerType" placeholder="Tipologia Offerta" class="!border-[#C8C8C8] [&_select]:!border-[#C8C8C8]">
                            <flux:select.option>Hotel e strutture ricettive</flux:select.option>
                            <flux:select.option>Attività ed Eventi</flux:select.option>
                            <flux:select.option>Ristorazione</flux:select.option>
                            <flux:select.option>Servizi per animali</flux:select.option>
                            <flux:select.option>Altro</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                <flux:field class="mt-4">
                    <flux:label class="!text-xs !text-gray-600">Descrizione</flux:label>
                    <flux:textarea wire:model="form.description" rows="4" placeholder="Descrivi il servizio che vorresti offrire, il luogo e alcune caratteristiche" class="!h-[117px] !border-[#C8C8C8] placeholder:!italic placeholder:!text-[#555555]" />
                </flux:field>

                <div class="mt-8 flex justify-end">
                    <flux:button type="submit" class="!rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white">Invia</flux:button>
                </div>
            </form>
        </div>
    </main>

    @include('partials.footer-minimal')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />
</div>
