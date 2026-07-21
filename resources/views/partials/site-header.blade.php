{{-- Header sito (condiviso: home, lavora con noi). Richiede $px definito dalla pagina. --}}
<header class="sticky top-0 z-50 border-b border-gray-150 bg-white/95 backdrop-blur">
    <div class="{{ $px }} flex h-20 items-center justify-between">
        <div class="flex items-center gap-10">
            <a href="{{ route('home') }}" class="shrink-0">
                <img src="{{ asset('img/logo.svg') }}" alt="AnimalAmo" class="w-[90px] h-auto">
            </a>
            {{-- Stato active della pagina corrente = stesso grassetto dell'hover. --}}
            @php $navActive = fn (string ...$patterns): string => request()->routeIs(...$patterns) ? 'font-bold' : ''; @endphp
            <nav class="hidden items-center gap-9 text-sm font-normal text-black lg:flex">
                <a href="{{ route('home') }}#holiday"   class="hover:font-bold {{ $navActive('home', 'holiday', 'holiday.*') }}">{{ __('nav.menu.holiday') }}</a>
                <a href="{{ route('eventi') }}" class="hover:font-bold {{ $navActive('eventi', 'eventi.*') }}">{{ __('nav.menu.events') }}</a>
                <a href="{{ route('smartbox') }}" class="hover:font-bold {{ $navActive('smartbox', 'smartbox.*') }}">{{ __('nav.menu.smartbox') }}</a>
                <a href="{{ route('news') }}" class="hover:font-bold {{ $navActive('news', 'news.*') }}">{{ __('nav.menu.news') }}</a>
                <a href="{{ route('community') }}" class="hover:font-bold {{ $navActive('community') }}">{{ __('nav.menu.community') }}</a>
                <a href="{{ route('about') }}" class="hover:font-bold {{ $navActive('about') }}">{{ __('nav.menu.about') }}</a>
                <a href="{{ route('work-with-us') }}" class="hover:font-bold {{ $navActive('work-with-us', 'work-with-us.*') }}">{{ __('nav.menu.become_partner') }}</a>
            </nav>
        </div>
        <div class="flex items-center gap-5 max-lg:gap-2">
            {{-- Lingua/valuta e Accedi: solo desktop, su mobile vivono nel menu hamburger --}}
            <flux:dropdown class="max-lg:hidden">
                <flux:button variant="ghost" size="sm" icon:trailing="chevron-down" class="!text-sm !font-normal !text-black font-sans">{{ strtoupper(app()->getLocale()) }} / EUR</flux:button>
                <flux:menu>
                    <flux:menu.group heading="{{ __('nav.language') }}">
                        {{-- Solo le lingue diverse da quella attiva (la corrente è già nel bottone). --}}
                        @foreach (\Mcamara\LaravelLocalization\Facades\LaravelLocalization::getSupportedLocales() as $code => $properties)
                            @if ($code !== app()->getLocale())
                                <flux:menu.item href="{{ route('locale.switch', $code) }}">{{ ucfirst($properties['native']) }}</flux:menu.item>
                            @endif
                        @endforeach
                    </flux:menu.group>
                    <flux:menu.group heading="{{ __('nav.currency') }}">
                        {{-- La valuta attiva (EUR) è nascosta come la lingua; USD è l'alternativa (statica). --}}
                        <flux:menu.item>USD $</flux:menu.item>
                    </flux:menu.group>
                </flux:menu>
            </flux:dropdown>

            @guest
                <flux:modal.trigger name="login">
                    <flux:button class="!rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white max-lg:hidden">{{ __('nav.login_register') }}</flux:button>
                </flux:modal.trigger>
            @endguest

            <div class="flex items-center gap-3">
                {{-- Preferiti e carrello: solo desktop, su mobile vivono nella tabbar --}}
                <flux:button variant="ghost" size="sm" square aria-label="{{ __('nav.favorites') }}" href="{{ route('preferiti') }}" class="!rounded-full !text-ink hover:!text-brand-magenta max-lg:!hidden">
                    <flux:icon.heart class="h-5 w-5" />
                </flux:button>
                <div class="max-lg:hidden">
                    <livewire:commerce.cart-badge />
                </div>

                @auth
                    {{-- Profilo: solo desktop, su mobile è la voce fissa della tabbar --}}
                    <flux:dropdown class="max-lg:hidden">
                        <flux:button variant="ghost" size="sm" square aria-label="{{ __('nav.profile') }}" class="!rounded-full !text-ink hover:!text-brand-cyan">
                            <flux:icon.profile class="h-5 w-5" />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item href="{{ route('profilo') }}">{{ __('nav.my_profile') }}</flux:menu.item>
                            <flux:menu.item href="{{ route('profilo.ordini') }}">{{ __('nav.my_orders') }}</flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" class="w-full">{{ __('nav.logout') }}</flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                @endauth

                {{-- Hamburger, solo mobile (XD app: header con logo + menu) --}}
                <flux:modal.trigger name="mobile-nav">
                    <flux:button variant="ghost" size="sm" square aria-label="{{ __('nav.menu_open') }}" class="!rounded-full !text-ink lg:hidden">
                        <flux:icon.bars-3 class="h-6 w-6" />
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>

    {{-- Menu mobile full-screen (XD app: Header stato "Menu open") --}}
    <flux:modal name="mobile-nav" :closable="false" class="w-full !m-0 !max-w-full !min-h-dvh !max-h-none !rounded-none bg-white !px-4 !py-6 lg:hidden">
        <div class="flex items-center justify-between">
            <a href="{{ route('home') }}">
                <img src="{{ asset('img/logo.svg') }}" alt="AnimalAmo" class="w-[72px]">
            </a>
            <flux:modal.close>
                <flux:button variant="ghost" size="sm" square aria-label="{{ __('nav.menu_close') }}" class="!rounded-full !text-ink">
                    <flux:icon.close class="h-4 w-4" />
                </flux:button>
            </flux:modal.close>
        </div>

        {{-- Link nav: 16px semibold con separatore sotto (XD: righe da 59px) --}}
        <nav class="mt-6 flex flex-col text-base font-semibold text-black">
            <a href="{{ route('home') }}#holiday" class="border-b border-gray-150 py-4">{{ __('nav.menu.holiday') }}</a>
            <a href="{{ route('eventi') }}" class="border-b border-gray-150 py-4">{{ __('nav.menu.events') }}</a>
            <a href="{{ route('smartbox') }}" class="border-b border-gray-150 py-4">{{ __('nav.menu.smartbox') }}</a>
            <a href="{{ route('news') }}" class="border-b border-gray-150 py-4">{{ __('nav.menu.news') }}</a>
            <a href="{{ route('community') }}" class="border-b border-gray-150 py-4">{{ __('nav.menu.community') }}</a>
            <a href="{{ route('about') }}" class="border-b border-gray-150 py-4">{{ __('nav.menu.about') }}</a>
            <a href="{{ route('work-with-us') }}" class="border-b border-gray-150 py-4">{{ __('nav.menu.become_partner') }}</a>

            {{-- Riga lingua/valuta a scomparsa (equivalente del dropdown desktop) --}}
            <div x-data="{ open: false }" class="border-b border-gray-150">
                <flux:button variant="ghost" x-on:click="open = ! open" class="!flex !w-full !items-center !justify-between !rounded-none !px-0 !py-4 !text-base !font-semibold !text-black hover:!bg-transparent [&>span]:!flex [&>span]:!w-full [&>span]:!items-center [&>span]:!justify-between">
                    {{ strtoupper(app()->getLocale()) }} / EUR
                    <flux:icon.chevron-down class="h-4 w-4 transition" x-bind:class="open && 'rotate-180'" />
                </flux:button>
                <div x-show="open" x-transition.opacity style="display: none" class="pb-4">
                    @foreach (\Mcamara\LaravelLocalization\Facades\LaravelLocalization::getSupportedLocales() as $code => $properties)
                        @if ($code !== app()->getLocale())
                            <a href="{{ route('locale.switch', $code) }}" class="block py-2 text-sm font-normal text-[#555555]">{{ ucfirst($properties['native']) }}</a>
                        @endif
                    @endforeach
                    <p class="py-2 text-sm font-normal text-[#555555]">USD $</p>
                </div>
            </div>
        </nav>

        @guest
            {{-- Pill gialla come da XD (182x39, r20, #EDFF00) --}}
            <div class="mt-10 flex justify-center">
                <flux:button x-on:click="$flux.modal('mobile-nav').close(); $flux.modal('login').show()" class="!rounded-full !bg-brand-yellow !px-8 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white">{{ __('nav.login_register') }}</flux:button>
            </div>
        @endguest
        @auth
            {{-- Niente "Il mio profilo": su mobile il profilo è la voce fissa della tabbar --}}
            <div class="mt-6 flex flex-col text-base font-semibold text-black">
                <a href="{{ route('profilo.ordini') }}" class="border-b border-gray-150 py-4">{{ __('nav.my_orders') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" class="!w-full !justify-start !rounded-none !px-0 !py-4 !text-base !font-semibold !text-black hover:!bg-transparent">{{ __('nav.logout') }}</flux:button>
                </form>
            </div>
        @endauth
    </flux:modal>
</header>
