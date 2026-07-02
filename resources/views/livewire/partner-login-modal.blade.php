{{-- Modale login partner (XD: "Pop-Up - Login – partner") --}}
<flux:modal name="partner-login" :closable="false" class="w-full !max-w-[660px] !rounded-[3px] bg-white !px-6 !py-6 backdrop:!bg-black/30">
    <div class="flex">
        <flux:button variant="ghost" size="xs" wire:click="backToLogin" icon="arrow-back" icon:class="!size-3.5" class="!gap-1.5 !px-0 !text-[13px] !font-normal !text-gray-400 hover:!bg-transparent hover:!text-ink">Indietro</flux:button>
    </div>

    <flux:heading level="2" class="mt-4 text-center !text-lg !font-medium !text-black">Accedi come Partner</flux:heading>

    <form wire:submit="login" class="mx-auto mt-14 w-full max-w-[472px]">
        <div class="space-y-4">
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
            <flux:button type="submit" class="!rounded-full !bg-brand-cyan !px-8 !text-[15px] !font-bold !text-white hover:!bg-[#4FB9DB]">Accedi</flux:button>
        </div>
    </form>

    <flux:text class="mt-16 mb-4 text-center !text-[15px] !text-[#0D171A]">Non sei ancora partner? <flux:link href="{{ route('work-with-us') }}" variant="ghost" class="!font-bold !text-[#F2BD2D]">Richiedi gli accessi</flux:link></flux:text>
</flux:modal>
