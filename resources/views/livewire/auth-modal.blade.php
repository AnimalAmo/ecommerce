{{-- Modale login/registrazione (XD: "Pop-Up - Login") --}}
<flux:modal name="login" :closable="false" class="w-full !max-w-[868px] !rounded-none bg-white !px-[32px] !py-6 backdrop:!bg-black/30">
    <div class="flex justify-end mb-6">
        <flux:modal.close>
            <flux:button variant="ghost" size="xs" class="!text-xs !font-normal !text-[#555555] hover:!text-black">
                <flux:icon.close class="h-3 w-3" />
                Chiudi
            </flux:button>
        </flux:modal.close>
    </div>

    <flux:heading level="2" class="text-center !text-2xl !font-semibold !text-black">Benvenuto</flux:heading>

    <div class="mt-6 flex flex-col gap-6 lg:flex-row">
        {{-- Card cliente --}}
        <flux:card class="w-full !rounded-[3px] !border-gray-150 !bg-white !px-4 !py-6 shadow-[0px_1px_10px_#0000001A] lg:w-[400px]">
            <flux:heading level="3" class="text-center !text-lg !font-medium !text-black">Accedi come Client</flux:heading>

            <form wire:submit="login">
                <div class="mt-6 space-y-4">
                    <flux:field>
                        <flux:label class="!text-xs !text-[#555555]">Email</flux:label>
                        <flux:input type="email" wire:model="form.email" placeholder="Email" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-[#555555]">Password</flux:label>
                        <flux:input type="password" wire:model="form.password" placeholder="Password" />
                    </flux:field>
                </div>
                <div class="mt-2 text-right">
                    <flux:link href="#" variant="ghost" class="!text-[13px] !font-normal !text-[#555555]">Password dimenticata</flux:link>
                </div>

                <div class="mt-6 flex justify-center">
                    <flux:button type="submit" class="!rounded-full !bg-[#0D171A] !px-9 !text-[15px] !font-bold !text-white hover:!bg-[#232A2C]">Accedi</flux:button>
                </div>
            </form>

            <flux:text class="mt-6 text-center !text-[15px] !text-[#0D171A]">Non hai un account? <flux:link as="button" wire:click="openRegister" variant="ghost" class="!font-bold !text-[#F2BD2D]">Registrati gratuitamente</flux:link></flux:text>
        </flux:card>

        {{-- Card partner --}}
        <flux:card class="w-full !rounded-[3px] !border-gray-150 !bg-white !px-4 !py-6 shadow-[0px_1px_10px_#0000001A] lg:w-[400px]">
            <flux:heading level="3" class="text-center !text-lg !font-medium !text-black">Accedi come Partner</flux:heading>

            <flux:text class="mt-10 text-center !text-[15px] text-black">Sei già un partner? <flux:link href="#" variant="ghost" class="!text-[#68CDEB] !font-bold">Accedi alla tua area riservata</flux:link></flux:text>

            <flux:separator class="my-6 !bg-[#DEDEDE]" />

            <flux:text class="text-center !text-[15px] !text-[#0D171A]">Vuoi diventare nostro partner? <flux:link href="#" variant="ghost" class="!font-bold !text-[#F2BD2D]">Compila il form</flux:link></flux:text>
        </flux:card>
    </div>
</flux:modal>
