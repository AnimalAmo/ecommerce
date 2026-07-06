{{-- Sidebar profilo (XD "Coupon" 399x288, condivisa dalle pagine Profilo): linea verticale #DEDEDE con segmento attivo ciano 3px --}}
{{-- Riceve $active: chiave della voce corrente. Voci senza pagina: ancora inerte. --}}
@php
    $profileNav = [
        ['key' => 'profilo', 'label' => 'Profilo', 'href' => route('profilo')],
        ['key' => 'pagamento', 'label' => 'Metodo di pagamento', 'href' => route('profilo.pagamento')],
        // TODO: pagine Sicurezza / I miei ordini / Eventi a cui partecipo (artboard XD dedicati)
        ['key' => 'sicurezza', 'label' => 'Sicurezza', 'href' => '#'],
        ['key' => 'ordini', 'label' => 'I miei ordini', 'href' => '#'],
        ['key' => 'eventi', 'label' => 'Eventi a cui partecipo', 'href' => '#'],
    ];
@endphp

<aside class="w-full shrink-0 lg:w-[399px]">
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
