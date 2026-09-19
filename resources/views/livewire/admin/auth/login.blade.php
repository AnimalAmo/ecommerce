<div>
    <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">Area di amministrazione</h1>
    <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">Accedi con le credenziali che ti abbiamo assegnato.</p>

    <form wire:submit="login" class="mt-7 flex flex-col gap-4">
        @error('email')
            <div class="flex gap-2.5 rounded-[3px] border border-[#F8D7D0] bg-[#FDF2F0] px-3.5 py-3" role="alert">
                <flux:icon.exclamation-triangle class="mt-px size-4 shrink-0 text-[#C4351A]" />
                <p class="m-0 text-[13px] leading-normal text-[#C4351A]">{{ $message }}</p>
            </div>
        @enderror

        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input type="email" wire:model="email" placeholder="Email" autocomplete="username" autofocus />
        </flux:field>

        <flux:field>
            <flux:label>Password</flux:label>
            <flux:input type="password" wire:model="password" placeholder="Password" autocomplete="current-password" viewable />
            <flux:error name="password" />
        </flux:field>

        <div class="flex items-center justify-between gap-3">
            <flux:checkbox wire:model="remember" label="Resta collegato" />
            <a href="{{ route('admin.password.request') }}" wire:navigate class="text-[13px] text-gray-600 hover:text-admin-teal">Hai dimenticato la password?</a>
        </div>

        <div class="mt-4 flex justify-center">
            <x-admin.button tone="primary" type="submit" class="!px-[34px]">Entra</x-admin.button>
        </div>
    </form>
</div>
