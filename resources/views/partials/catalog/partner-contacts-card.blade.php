{{--
    Prende il posto del box prenotazione sulle schede dei partner che non
    usano il pagamento online (richiesta della cliente, 29/09/2026): la scheda
    si consulta e il partner si contatta, senza passare dal checkout.

    $contacts arriva da App\Services\Partner\PartnerContacts e può essere null:
    di un partner senza profilo non sappiamo nemmeno la ragione sociale, e in
    quel caso resta la sola dicitura. Cosa si pubblica (consenso, modalità di
    incasso) lo decide il service: qui si mostra quello che arriva.

    $showHours (facoltativa, default true) = false sulle attività, dove gli
    orari stanno già nell'elenco informazioni della scheda.
--}}
@php
    $linkIcons = [
        'phone' => 'phone',
        'whatsapp' => 'chat-bubble-left-right',
        'email' => 'envelope',
        'website' => 'globe-alt',
    ];
@endphp
<div class="rounded-[4px] border border-[#DEDEDE] bg-white p-[22px]">
    <h2 class="text-[22px] font-bold leading-[30px] text-black">{{ __('catalog.contacts.title') }}</h2>
    <p class="mt-2 text-[15px] leading-[22px] text-[#627277]">{{ __('catalog.contacts.intro') }}</p>

    @if (filled($contacts ?? null))
        <dl class="mt-5 space-y-4">
            @if (! empty($contacts['business_name']))
                <div>
                    <dt class="text-[13px] leading-[18px] text-[#627277]">{{ __('catalog.contacts.business_name') }}</dt>
                    <dd class="text-[15px] font-semibold leading-[22px] text-[#0D171A]">{{ $contacts['business_name'] }}</dd>
                </div>
            @endif
            @if (! empty($contacts['address']))
                <div>
                    <dt class="text-[13px] leading-[18px] text-[#627277]">{{ __('catalog.contacts.address') }}</dt>
                    <dd class="flex items-start gap-2 text-[15px] leading-[22px] text-[#0D171A]">
                        <flux:icon.pin class="mt-[3px] h-4 w-4 shrink-0" />
                        <span>{{ $contacts['address'] }}</span>
                    </dd>
                </div>
            @endif
            @if (($showHours ?? true) && ! empty($contacts['opening_hours']))
                <div>
                    <dt class="text-[13px] leading-[18px] text-[#627277]">{{ __('catalog.contacts.opening_hours') }}</dt>
                    <dd class="flex items-start gap-2 text-[15px] leading-[22px] text-[#0D171A]">
                        <flux:icon.time class="mt-[3px] h-4 w-4 shrink-0" />
                        <span>{{ $contacts['opening_hours'] }}</span>
                    </dd>
                </div>
            @endif
        </dl>

        {{-- Recapiti pubblici (risposta della cliente, 26/09/2026, punto 6): un link
             per voce, nell'ordine del service. WhatsApp e sito escono dalla pagina. --}}
        @if (! empty($contacts['links']))
            <ul class="mt-5 space-y-3">
                @foreach ($contacts['links'] as $link)
                    <li wire:key="partner-contact-{{ $link['type'] }}">
                        <a href="{{ $link['href'] }}"
                           @if (in_array($link['type'], ['whatsapp', 'website'], true)) target="_blank" rel="noopener noreferrer" @endif
                           aria-label="{{ __('catalog.contacts.'.$link['type']) }}: {{ $link['label'] }}"
                           class="flex items-start gap-2 text-[15px] font-semibold leading-[22px] text-[#0D171A] underline-offset-2 hover:underline">
                            <flux:icon :icon="$linkIcons[$link['type']]" class="mt-[3px] h-4 w-4 shrink-0" />
                            <span class="min-w-0 break-words">{{ $link['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if (! empty($contacts['booking_url']))
            <flux:button href="{{ $contacts['booking_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-6 !flex !h-[39px] w-full items-center justify-center !rounded-full !border-0 !bg-brand-yellow !px-0 text-sm !font-bold !text-[#0D171A] !shadow-none">
                {{ __('checkout.on_site.pay_on_website') }}
            </flux:button>
        @endif
    @endif
</div>
