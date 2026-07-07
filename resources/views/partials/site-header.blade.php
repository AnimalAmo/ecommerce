{{-- Header sito (condiviso: home, lavora con noi). Richiede $px definito dalla pagina. --}}
<header class="sticky top-0 z-50 border-b border-gray-150 bg-white/95 backdrop-blur">
    <div class="{{ $px }} flex h-20 items-center justify-between">
        <div class="flex items-center gap-10">
            <a href="/" class="shrink-0">
                <img src="{{ asset('img/logo.svg') }}" alt="AnimalAmo" class="w-[90px] h-auto">
            </a>
            {{-- Stato active della pagina corrente = stesso grassetto dell'hover. --}}
            @php $navActive = fn (string ...$patterns): string => request()->routeIs(...$patterns) ? 'font-bold' : ''; @endphp
            <nav class="hidden items-center gap-9 text-sm font-normal text-black lg:flex">
                <a href="/#holiday"   class="hover:font-bold {{ $navActive('home', 'holiday', 'holiday.*') }}">{{ __('nav.menu.holiday') }}</a>
                <a href="{{ route('eventi') }}" class="hover:font-bold {{ $navActive('eventi', 'eventi.*') }}">{{ __('nav.menu.events') }}</a>
                <a href="{{ route('smartbox') }}" class="hover:font-bold {{ $navActive('smartbox', 'smartbox.*') }}">{{ __('nav.menu.smartbox') }}</a>
                <a href="{{ route('news') }}" class="hover:font-bold {{ $navActive('news', 'news.*') }}">{{ __('nav.menu.news') }}</a>
                <a href="{{ route('community') }}" class="hover:font-bold {{ $navActive('community') }}">{{ __('nav.menu.community') }}</a>
                <a href="{{ route('about') }}" class="hover:font-bold {{ $navActive('about') }}">{{ __('nav.menu.about') }}</a>
                <a href="{{ route('work-with-us') }}" class="hover:font-bold {{ $navActive('work-with-us', 'work-with-us.*') }}">{{ __('nav.menu.become_partner') }}</a>
            </nav>
        </div>
        <div class="flex items-center gap-5">
            <flux:dropdown>
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
                    <flux:button class="!rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white">{{ __('nav.login_register') }}</flux:button>
                </flux:modal.trigger>
            @endguest

            <div class="flex items-center gap-3">
                <flux:button variant="ghost" size="sm" square aria-label="{{ __('nav.favorites') }}" href="{{ route('preferiti') }}" class="!rounded-full !text-ink hover:!text-brand-magenta">
                    <flux:icon.heart class="h-5 w-5" />
                </flux:button>
                <livewire:commerce.cart-badge />

                @auth
                    <flux:dropdown>
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
            </div>
        </div>
    </div>
</header>
