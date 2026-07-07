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
                <a href="/#holiday"   class="hover:font-bold {{ $navActive('home', 'holiday', 'holiday.*') }}">Holiday</a>
                <a href="{{ route('eventi') }}" class="hover:font-bold {{ $navActive('eventi', 'eventi.*') }}">Attività ed Eventi</a>
                <a href="{{ route('smartbox') }}" class="hover:font-bold {{ $navActive('smartbox', 'smartbox.*') }}">Smartbox</a>
                <a href="{{ route('news') }}" class="hover:font-bold {{ $navActive('news', 'news.*') }}">News</a>
                <a href="{{ route('community') }}" class="hover:font-bold {{ $navActive('community') }}">Community</a>
                <a href="{{ route('about') }}" class="hover:font-bold {{ $navActive('about') }}">Chi siamo</a>
                <a href="{{ route('work-with-us') }}" class="hover:font-bold {{ $navActive('work-with-us', 'work-with-us.*') }}">Diventa Partner</a>
            </nav>
        </div>
        <div class="flex items-center gap-5">
            <flux:dropdown>
                <flux:button variant="ghost" size="sm" icon:trailing="chevron-down" class="!text-sm !font-normal !text-black font-sans">ITA / EUR</flux:button>
                <flux:menu>
                    <flux:menu.group heading="Lingua">
                        <flux:menu.item>Italiano</flux:menu.item>
                        <flux:menu.item>English</flux:menu.item>
                    </flux:menu.group>
                    <flux:menu.group heading="Valuta">
                        <flux:menu.item>EUR &euro;</flux:menu.item>
                        <flux:menu.item>USD $</flux:menu.item>
                    </flux:menu.group>
                </flux:menu>
            </flux:dropdown>

            @guest
                <flux:modal.trigger name="login">
                    <flux:button class="!rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white">Accedi / Registrati</flux:button>
                </flux:modal.trigger>
            @endguest

            <div class="flex items-center gap-3">
                <flux:button variant="ghost" size="sm" square aria-label="Preferiti" href="{{ route('preferiti') }}" class="!text-ink hover:!text-brand-magenta">
                    <flux:icon.heart class="h-5 w-5" />
                </flux:button>
                <livewire:commerce.cart-badge />

                @auth
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" square aria-label="Profilo" class="!text-ink hover:!text-brand-cyan">
                            <flux:icon.profile class="h-5 w-5" />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item href="{{ route('profilo') }}">Il mio profilo</flux:menu.item>
                            <flux:menu.item href="{{ route('profilo.ordini') }}">I miei ordini</flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" class="w-full">Esci</flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                @endauth
            </div>
        </div>
    </div>
</header>
