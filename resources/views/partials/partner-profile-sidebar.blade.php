{{-- Voci profilo: titolo + navigazione laterale tra le 3 pagine del profilo partner. --}}
@php
    $linkBase = 'flex items-center gap-3 rounded-[8px] p-[10px] text-base';
    $linkActive = 'bg-[#2B2B2B] font-bold text-white';
    $linkIdle = 'font-normal text-[#0D171A] hover:bg-[#2B2B2B] hover:text-white';
@endphp
<nav class="w-full shrink-0 py-6 md:w-[260px]">
    <h2 class="mb-6 text-2xl font-bold text-[#0D171A]">{{ __('partner.profile.title') }}</h2>

    <ul class="flex flex-col gap-1">
        <li>
            <a href="{{ route('partner.profile') }}" class="{{ $linkBase }} {{ request()->routeIs('partner.profile') ? $linkActive : $linkIdle }}">
                <flux:icon.profile class="size-5 shrink-0" />
                {{ __('partner.profile.info_heading') }}
            </a>
        </li>
        <li>
            <a href="{{ route('partner.profile.payment') }}" class="{{ $linkBase }} {{ request()->routeIs('partner.profile.payment') ? $linkActive : $linkIdle }}">
                <flux:icon.credit-card class="size-5 shrink-0" />
                {{ __('partner.profile.nav_payment') }}
            </a>
        </li>
        <li>
            <a href="{{ route('partner.profile.security') }}" class="{{ $linkBase }} {{ request()->routeIs('partner.profile.security') ? $linkActive : $linkIdle }}">
                <flux:icon.lock class="size-5 shrink-0" />
                {{ __('partner.profile.nav_security') }}
            </a>
        </li>
    </ul>
</nav>
