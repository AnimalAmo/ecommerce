{{-- Voci profilo: navigazione laterale tra le 3 pagine del profilo partner. --}}
@php
    $items = [
        ['route' => 'partner.profile', 'label' => __('partner.profile.nav_profile')],
        ['route' => 'partner.profile.payment', 'label' => __('partner.profile.nav_payment')],
        ['route' => 'partner.profile.security', 'label' => __('partner.profile.nav_security')],
    ];
@endphp
<nav class="w-full shrink-0 md:w-[260px]">
    <ul class="flex flex-col gap-1">
        @foreach ($items as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <li>
                <a href="{{ route($item['route']) }}"
                    class="block rounded-[8px] px-4 py-3 text-[15px] {{ $active ? 'bg-brand-cyan/15 font-bold text-ink' : 'font-normal text-[#627277] hover:bg-gray-100' }}">
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
