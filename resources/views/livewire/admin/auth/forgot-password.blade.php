<div>
    <a href="{{ route('admin.login') }}" wire:navigate class="mb-[22px] inline-flex items-center gap-1.5 text-[13px] text-gray-400 hover:text-admin-teal">
        <flux:icon.chevron-left class="size-3.5" />{{ __('admin.actions.back') }}
    </a>

    @if ($sent)
        <div class="text-center">
            <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-brand-cyan/15">
                <flux:icon.envelope class="size-[27px] text-admin-teal" />
            </div>
            <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">{{ __('admin.auth.forgot.sent_heading') }}</h1>
            <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">{{ __('admin.auth.forgot.sent_body', ['email' => $email]) }}</p>

            <div class="mt-8 flex justify-center">
                <x-admin.button tone="primary" :href="route('admin.login')" wire:navigate class="!px-[34px]">{{ __('admin.auth.forgot.back_to_login') }}</x-admin.button>
            </div>

            <p class="mt-[26px] text-[13px] leading-normal text-gray-600">{{ __('admin.auth.forgot.sent_note', ['minutes' => $expiresIn]) }}</p>
        </div>
    @else
        <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">{{ __('admin.auth.forgot.heading') }}</h1>
        <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">{{ __('admin.auth.forgot.intro') }}</p>

        <form wire:submit="send" class="mt-7">
            <flux:input type="email" :label="__('admin.auth.email')" wire:model="email" :placeholder="__('admin.auth.email')" autocomplete="username" autofocus />

            <div class="mt-8 flex justify-center">
                <x-admin.button tone="primary" type="submit" class="!px-[34px]">{{ __('admin.auth.forgot.submit') }}</x-admin.button>
            </div>
        </form>
    @endif
</div>
