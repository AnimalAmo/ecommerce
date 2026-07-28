{{-- Sidebar profilo (XD "Coupon" 399x288, condivisa dalle pagine Profilo): linea verticale #DEDEDE con segmento attivo ciano 3px --}}
{{-- Riceve $active: chiave della voce corrente. Voci senza pagina: ancora inerte. --}}
{{-- Su mobile (XD app) la sidebar sparisce: la pagina "Profilo" mostra il menu a pillole qui sotto,
     le altre pagine il link "Indietro" in cima al contenuto. --}}
@php
    // Ponte B2C → B2B: chi è già partner salta in dashboard, chi ha una richiesta
    // aperta la riprende dallo step 1 (precompilato), gli altri vedono il form.
    $partnerUser = auth()->user();
    $partnerOpenApplication = $partnerUser?->hasRole('partner') ? null : $partnerUser?->openPartnerApplication();
    $partnerEntry = match (true) {
        $partnerUser?->hasRole('partner') => ['key' => 'partner', 'label' => __('profile.nav_partner_area'), 'href' => route('partner.dashboard')],
        $partnerOpenApplication !== null => ['key' => 'partner', 'label' => __('profile.nav_partner_request_sent'), 'href' => route('partner.register')],
        default => ['key' => 'partner', 'label' => __('profile.nav_become_partner'), 'href' => route('work-with-us')],
    };

    $profileNav = [
        ['key' => 'profilo', 'label' => __('profile.nav_profile'), 'href' => route('profilo')],
        ['key' => 'pagamento', 'label' => __('profile.nav_payment'), 'href' => route('profilo.pagamento')],
        ['key' => 'sicurezza', 'label' => __('profile.nav_security'), 'href' => route('profilo.sicurezza')],
        ['key' => 'ordini', 'label' => __('profile.nav_orders'), 'href' => route('profilo.ordini')],
        ['key' => 'eventi', 'label' => __('profile.nav_events'), 'href' => route('profilo.eventi')],
        $partnerEntry,
    ];

    // Menu mobile: ordine e etichette dell'artboard app "Profilo" (≠ ordine sidebar desktop).
    $profileMenu = [
        ['icon' => 'profile', 'label' => __('profile.nav_personal_data'), 'href' => route('profilo.anagrafica')],
        ['icon' => 'card', 'label' => __('profile.nav_payment_data'), 'href' => route('profilo.pagamento')],
        ['icon' => 'ticket', 'label' => __('profile.nav_orders'), 'href' => route('profilo.ordini')],
        ['icon' => 'event', 'label' => __('profile.nav_events'), 'href' => route('profilo.eventi')],
        ['icon' => 'lock', 'label' => __('profile.nav_security'), 'href' => route('profilo.sicurezza')],
        ['icon' => 'partner', 'label' => $partnerEntry['label'], 'href' => $partnerEntry['href']],
    ];
@endphp

@if ($active === 'profilo' && request()->routeIs('profilo'))
    {{-- Voci 342x40: bianche SENZA bordo (lo stroke XD è spento), r5 e ombra 0/0/5.5 nero 11%;
         icona 15, etichetta 15 medium, chevron a destra, passo 53 --}}
    <div class="lg:hidden">
        <h1 class="text-lg font-bold leading-none text-[#0D171A]">{{ __('profile.nav_profile') }}</h1>

        <nav class="mt-[18px] space-y-[13px]">
            @foreach ($profileMenu as $item)
                <a href="{{ $item['href'] }}" wire:key="menu-{{ $item['icon'] }}" class="flex h-10 items-center gap-[9px] rounded-[5px] bg-white pl-[9px] pr-[17px] shadow-[0px_0px_6px_#0000001C]">
                    <span class="flex w-[15px] shrink-0 justify-center text-[#0D171A]">
                        @switch($item['icon'])
                            @case('profile') <flux:icon.profile class="h-[15px] w-[15px]" /> @break
                            @case('card') <flux:icon.credit-card class="!h-[15px] !w-[15px]" /> @break
                            @case('ticket') <flux:icon.ticket class="!h-[15px] !w-[15px]" /> @break
                            @case('event') <flux:icon.calendar class="!h-[15px] !w-[15px]" /> @break
                            @case('lock') <flux:icon.lock class="h-[15px] w-[15px]" /> @break
                            @case('partner') <flux:icon.briefcase class="!h-[15px] !w-[15px]" /> @break
                        @endswitch
                    </span>
                    <span class="flex-1 truncate text-[15px] font-medium leading-none text-[#0D171A]">{{ $item['label'] }}</span>
                    <flux:icon.arrow-forward class="h-[13px] w-[8px] shrink-0 text-[#171717]" />
                </a>
            @endforeach
        </nav>
    </div>
@endif

<aside class="w-full shrink-0 max-lg:hidden lg:w-[399px]">
    <div class="rounded-[3px] border border-[#E9E9E9] bg-white p-6 shadow-[0px_1px_10px_#0000001A]">
        <nav class="relative">
            <div class="absolute inset-y-[6px] left-0 w-px bg-[#DEDEDE]" aria-hidden="true"></div>
            <ul>
                @foreach ($profileNav as $item)
                    <li class="relative">
                        <a href="{{ $item['href'] }}" class="relative flex h-[50px] items-center pl-[17px] text-lg font-medium leading-none {{ $active === $item['key'] ? 'text-[#68CDEB]' : 'text-[#2B2B2B]' }}">
                            @if ($active === $item['key'])
                                <span class="absolute -left-px top-1/2 h-[38px] w-[3px] -translate-y-1/2 bg-[#68CDEB]" aria-hidden="true"></span>
                            @endif
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
