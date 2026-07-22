{{-- Header B2B (onboarding partner): logo + dropdown "Aiuto". Richiede $px definito dalla pagina. --}}
<header class="border-b border-gray-150 bg-white">
    <div class="{{ $px }} flex h-20 items-center justify-between">
        <a href="{{ route('home') }}" class="shrink-0">
            <img src="{{ asset('img/logo.svg') }}" alt="AnimalAmo" class="h-auto w-[120px]">
        </a>
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="ghost" size="sm" icon:trailing="chevron-down" class="font-sans !text-[15px] !font-normal !text-ink">{{ __('partner.nav_help') }}</flux:button>
            <flux:menu>
                {{-- Contattaci e Assistenza unificati nella pagina Contattaci (lug 2026) --}}
                <flux:menu.item href="{{ route('contact') }}">{{ __('partner.help_contact') }}</flux:menu.item>
                <flux:menu.item href="#">{{ __('partner.help_faq') }}</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>
</header>
