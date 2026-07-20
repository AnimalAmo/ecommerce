{{-- Tabbar fissa in basso, solo mobile (XD app: symbol "Tabbar", barra bianca h80, label 11px) --}}
@php
    $tabActive = fn (string ...$patterns): bool => request()->routeIs(...$patterns);
    $tabClass = fn (bool $active): string => $active ? 'text-brand-cyan' : 'text-[#2B2B2B]';
@endphp
<nav class="fixed inset-x-0 bottom-0 z-40 flex h-20 items-start justify-around border-t border-gray-150 bg-white pt-3 lg:hidden">
    <a href="{{ route('home') }}" class="flex w-16 flex-col items-center gap-1.5 {{ $tabClass($tabActive('home', 'holiday', 'holiday.*', 'eventi', 'eventi.*', 'smartbox', 'smartbox.*')) }}">
        <flux:icon.home class="h-6 w-6" />
        <span class="text-[11px] {{ $tabActive('home', 'holiday', 'holiday.*', 'eventi', 'eventi.*', 'smartbox', 'smartbox.*') ? 'font-medium' : '' }}">{{ __('nav.tabbar.explore') }}</span>
    </a>
    <a href="{{ route('preferiti') }}" class="flex w-16 flex-col items-center gap-1.5 {{ $tabClass($tabActive('preferiti')) }}">
        <flux:icon.heart class="h-6 w-6" />
        <span class="text-[11px] {{ $tabActive('preferiti') ? 'font-medium' : '' }}">{{ __('nav.favorites') }}</span>
    </a>
    <a href="{{ route('community') }}" class="flex w-16 flex-col items-center gap-1.5 {{ $tabClass($tabActive('community')) }}">
        <flux:icon.community class="h-6 w-8" />
        <span class="text-[11px] {{ $tabActive('community') ? 'font-medium' : '' }}">{{ __('nav.tabbar.community') }}</span>
    </a>
    <a href="{{ route('carrello') }}" class="flex w-16 flex-col items-center gap-1.5 {{ $tabClass($tabActive('carrello', 'checkout', 'checkout.*')) }}">
        <flux:icon.cart class="h-6 w-6" />
        <span class="text-[11px] {{ $tabActive('carrello', 'checkout', 'checkout.*') ? 'font-medium' : '' }}">{{ __('nav.tabbar.cart') }}</span>
    </a>
    <a href="{{ route('profilo') }}" class="flex w-16 flex-col items-center gap-1.5 {{ $tabClass($tabActive('profilo', 'profilo.*')) }}">
        <flux:icon.profile class="h-6 w-6" />
        <span class="text-[11px] {{ $tabActive('profilo', 'profilo.*') ? 'font-medium' : '' }}">{{ __('nav.profile') }}</span>
    </a>
</nav>
{{-- Spaziatore: evita che la tabbar copra il fondo pagina --}}
<div class="h-20 lg:hidden"></div>
