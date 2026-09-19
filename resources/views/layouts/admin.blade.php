<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ isset($title) ? $title.' · ' : '' }}Amministrazione AnimalAmo</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        {{-- Niente @fluxAppearance: il pannello è solo in tema chiaro, come il design. --}}
    </head>
    <body class="min-h-screen bg-gray-100 font-sans text-ink antialiased">
        @php
            $navigation = app(\App\Support\Admin\AdminNavigation::class)->groups();
            $admin = auth()->user();
            $initials = mb_strtoupper(mb_substr((string) $admin?->first_name, 0, 1).mb_substr((string) $admin?->last_name, 0, 1)) ?: 'AA';
        @endphp

        <div x-data="{ drawer: false }" @keydown.escape.window="drawer = false" class="flex min-h-screen items-stretch">
            {{-- Colonna di navigazione: fissa su desktop, cassetto sotto i 1024px. --}}
            <aside
                :class="drawer ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-full'"
                class="z-40 flex h-screen w-[248px] shrink-0 flex-col bg-admin-rail max-lg:fixed max-lg:inset-y-0 max-lg:left-0 max-lg:-translate-x-full max-lg:transition-transform lg:sticky lg:top-0"
            >
                <div class="flex h-[68px] items-center justify-between gap-2 border-b border-gray-150 bg-white px-5">
                    <a href="{{ route('admin.home') }}" wire:navigate>
                        <img src="{{ asset('img/logo.svg') }}" alt="AnimalAmo" class="block h-auto w-24">
                    </a>
                    <flux:button variant="ghost" size="sm" icon="x-mark" x-on:click="drawer = false" aria-label="Chiudi menu" class="lg:!hidden" />
                </div>

                <div class="flex items-center gap-[7px] px-5 pt-[18px] pb-3.5 text-brand-cyan">
                    <flux:icon.animal class="size-[15px] shrink-0" />
                    <span class="text-[10.5px] font-bold tracking-[.11em] uppercase">Amministrazione</span>
                </div>

                <nav class="flex-1 overflow-y-auto px-3.5 pb-5" aria-label="Pannello">
                    @foreach ($navigation as $group)
                        <div class="mb-4">
                            <p class="mb-[7px] px-2 text-[10px] font-bold tracking-[.11em] text-admin-rail-label uppercase">{{ $group['label'] }}</p>
                            <div class="flex flex-col gap-0.5">
                                @foreach ($group['items'] as $item)
                                    @php $active = request()->routeIs(...$item['active']); @endphp
                                    <a
                                        href="{{ route($item['route']) }}"
                                        wire:navigate
                                        @if ($active) aria-current="page" @endif
                                        @class([
                                            'flex h-[38px] w-full items-center justify-between gap-2 rounded-lg px-2.5 text-left text-sm',
                                            'bg-admin-rail-active font-bold text-white shadow-[inset_2px_0_0_var(--color-brand-cyan)]' => $active,
                                            'text-admin-rail-text hover:bg-admin-rail-active/60 hover:text-white' => ! $active,
                                        ])
                                    >
                                        <span class="truncate">{{ $item['label'] }}</span>
                                        @if ($item['count'])
                                            <span @class([
                                                'inline-flex h-[19px] min-w-[21px] items-center justify-center rounded-full px-1.5 text-[11px] font-bold',
                                                'bg-brand-cyan text-ink' => $active,
                                                'bg-admin-rail-pill text-admin-rail-muted' => ! $active,
                                            ])>{{ $item['count'] }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>

                <div class="flex flex-col gap-3.5 border-t border-admin-rail-line px-5 py-4">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="flex items-center gap-2 text-[13px] font-semibold text-admin-rail-text hover:text-brand-cyan">
                        <flux:icon.arrow-top-right-on-square class="size-[15px] shrink-0" />
                        Vai al sito pubblico
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <flux:button type="submit" variant="ghost" size="sm" icon="x-mark" class="!h-auto !px-0 !text-[13px] !font-semibold !text-admin-rail-muted hover:!bg-transparent hover:!text-white">
                            Esci
                        </flux:button>
                    </form>
                </div>
            </aside>

            <div x-show="drawer" x-cloak x-on:click="drawer = false" class="fixed inset-0 z-30 bg-ink/50 lg:hidden"></div>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 flex h-[68px] items-center gap-3.5 border-b border-gray-150 bg-white px-[clamp(16px,2.2vw,28px)]">
                    <flux:button variant="outline" size="sm" icon="bars-3" x-on:click="drawer = true" aria-label="Apri menu" class="lg:!hidden" />

                    <form method="GET" action="{{ route('admin.search') }}" class="min-w-0 max-w-[420px] flex-1" role="search">
                        <flux:input
                            type="search"
                            name="q"
                            value="{{ request()->routeIs('admin.search') ? request('q') : '' }}"
                            icon="magnifying-glass"
                            placeholder="Cerca schede, iscritti, ordini…"
                            aria-label="Cerca nel pannello"
                        />
                    </form>

                    <div class="flex-1"></div>

                    <div class="flex shrink-0 items-center gap-2.5">
                        <span title="{{ $admin?->email }}" class="flex size-[38px] items-center justify-center rounded-full bg-brand-cyan-bg text-[13px] font-bold text-admin-teal">{{ $initials }}</span>
                    </div>
                </header>

                <main class="w-full flex-1 px-[clamp(16px,2.2vw,28px)] pt-[clamp(20px,2vw,28px)] pb-[72px]">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <flux:toast position="bottom end" />

        @livewireScripts
        @fluxScripts
    </body>
</html>
