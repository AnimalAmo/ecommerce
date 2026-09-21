<div>
    @if ($state === 'invalid')
        <div class="text-center">
            <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-brand-magenta/15">
                <flux:icon.exclamation-triangle class="size-[27px] text-[#C2186F]" />
            </div>
            <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">{{ __('admin.auth.reset.invalid_heading') }}</h1>
            <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">{{ __('admin.auth.reset.invalid_body') }}</p>
            <div class="mt-8 flex justify-center">
                <x-admin.button tone="primary" :href="route('admin.password.request')" wire:navigate class="!px-[34px]">{{ __('admin.auth.reset.invalid_action') }}</x-admin.button>
            </div>
        </div>
    @elseif ($state === 'done')
        <div class="text-center">
            <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-brand-cyan">
                <flux:icon.check class="size-[27px] text-white" />
            </div>
            <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">{{ __('admin.auth.reset.done_heading') }}</h1>
            <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">{{ __('admin.auth.reset.done_body') }}</p>
            <div class="mt-8 flex justify-center">
                <x-admin.button tone="primary" :href="route('admin.login')" wire:navigate class="!px-[34px]">{{ __('admin.auth.reset.done_action') }}</x-admin.button>
            </div>
        </div>
    @else
        <div class="text-center">
            <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">{{ __('admin.auth.reset.heading') }}</h1>
            <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">{{ __('admin.auth.reset.intro', ['email' => $email]) }}</p>
        </div>

        <form wire:submit="save" class="mt-7 flex flex-col gap-4">
            <flux:input type="password" :label="__('admin.auth.reset.password')" wire:model="password" autocomplete="new-password" viewable />
            <flux:input type="password" :label="__('admin.auth.reset.password_confirmation')" wire:model="password_confirmation" autocomplete="new-password" viewable />
            <p class="m-0 text-[12.5px] leading-normal text-gray-400">{{ __('admin.auth.reset.rules') }}</p>

            <div class="mt-4 flex justify-center">
                <x-admin.button tone="primary" type="submit" class="!px-[34px]">{{ __('admin.auth.reset.submit') }}</x-admin.button>
            </div>
        </form>
    @endif
</div>
