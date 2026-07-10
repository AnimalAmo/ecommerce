{{-- Header dashboard B2B: logo + nav area partner + dropdown "Aiuto". Richiede $px definito dalla pagina. --}}
<header class="border-b border-gray-150 bg-white">
    <div class="{{ $px }} flex h-20 items-center justify-between gap-6">
        <a href="{{ route('partner.dashboard') }}" class="shrink-0">
            <img src="{{ asset('img/logo.svg') }}" alt="AnimalAmo" class="h-auto w-[120px]">
        </a>
        <nav class="hidden flex-1 items-center justify-center gap-8 text-sm lg:flex">
            <a href="{{ route('partner.dashboard') }}" class="{{ request()->routeIs('partner.dashboard') ? 'font-bold text-brand-cyan' : 'font-normal text-black hover:font-bold hover:text-brand-cyan' }}">{{ __('partner.nav_dashboard') }}</a>
            <a href="{{ route('partner.service.create') }}" class="{{ request()->routeIs('partner.service.create', 'partner.structure.*', 'partner.activity.*', 'partner.smartbox.*') ? 'font-bold text-brand-cyan' : 'font-normal text-black hover:font-bold hover:text-brand-cyan' }}">{{ __('partner.nav_create_service') }}</a>
            <a href="#" class="font-normal text-black hover:font-bold hover:text-brand-cyan">{{ __('partner.nav_my_services') }}</a>
            <a href="#" class="font-normal text-black hover:font-bold hover:text-brand-cyan">{{ __('partner.nav_bookings') }}</a>
            <a href="{{ route('partner.profile') }}" class="{{ request()->routeIs('partner.profile', 'partner.profile.*') ? 'font-bold text-brand-cyan' : 'font-normal text-black hover:font-bold hover:text-brand-cyan' }}">{{ __('partner.nav_profile') }}</a>
        </nav>
        <div class="flex shrink-0 items-center gap-2">
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" size="sm" icon:trailing="chevron-down" class="font-sans !text-[15px] !font-normal !text-ink">{{ __('partner.nav_help') }}</flux:button>
                <flux:menu>
                    <flux:menu.item href="#">{{ __('partner.help_contact') }}</flux:menu.item>
                    <flux:menu.item href="#">{{ __('partner.help_support') }}</flux:menu.item>
                    <flux:menu.item href="#">{{ __('partner.help_faq') }}</flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            {{-- Menu account partner: profilo + logout --}}
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" size="sm" square aria-label="{{ __('partner.nav_profile') }}" class="!rounded-full !text-ink hover:!text-brand-cyan">
                    <flux:icon.profile class="size-5" />
                </flux:button>
                <flux:menu>
                    <flux:menu.item href="{{ route('partner.profile') }}">{{ __('partner.nav_profile') }}</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" class="w-full">{{ __('nav.logout') }}</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
</header>
