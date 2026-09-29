{{--
    «Informazioni utili» sotto il box prenotazione: indirizzo pubblico e orari,
    che la cliente vuole visibili in entrambe le modalità (risposta del
    26/09/2026, punto 6). Telefono, WhatsApp, email e sito qui NON si disegnano
    mai, anche quando arrivano.

    Si usa ovunque la scheda non mostra la card contatti, cioè quando non
    $paysOnSite. Di solito il partner incassa online e PartnerContacts i link
    non li manda. Fanno eccezione le attività e gli eventi gratuiti
    («Partecipa», hasJoinCta()) di un partner che incassa in struttura: lì
    paysOnSite è falso, PartnerContacts i link li manda, e questo riquadro li
    scarta. Se mostrarli anche lì è una scelta di prodotto ancora aperta.

    Con il pagamento in struttura questo riquadro non serve: la card contatti
    che prende il posto del box prenotazione mostra già tutto.

    $contacts arriva da App\Services\Partner\PartnerContacts (anche null).
    $showHours (facoltativa, default true) = false sulle attività, dove gli
    orari stanno già nell'elenco informazioni. $infoClass (facoltativa) regola
    il margine dal blocco sopra. Senza niente da mostrare non disegna niente.
--}}
@php
    $showHours ??= true;
@endphp
@if (\App\Services\Partner\PartnerContacts::hasPublicInfo($contacts ?? null, $showHours))
    <div class="{{ $infoClass ?? '' }} rounded-[4px] border border-[#DEDEDE] bg-white p-[22px]">
        <h2 class="text-[22px] font-bold leading-[30px] text-black">{{ __('catalog.contacts.info_title') }}</h2>

        <dl class="mt-5 space-y-4">
            @if (! empty($contacts['address']))
                <div>
                    <dt class="text-[13px] leading-[18px] text-[#627277]">{{ __('catalog.contacts.address') }}</dt>
                    <dd class="flex items-start gap-2 text-[15px] leading-[22px] text-[#0D171A]">
                        <flux:icon.pin class="mt-[3px] h-4 w-4 shrink-0" />
                        <span>{{ $contacts['address'] }}</span>
                    </dd>
                </div>
            @endif
            @if ($showHours && ! empty($contacts['opening_hours']))
                <div>
                    <dt class="text-[13px] leading-[18px] text-[#627277]">{{ __('catalog.contacts.opening_hours') }}</dt>
                    <dd class="flex items-start gap-2 text-[15px] leading-[22px] text-[#0D171A]">
                        <flux:icon.time class="mt-[3px] h-4 w-4 shrink-0" />
                        <span>{{ $contacts['opening_hours'] }}</span>
                    </dd>
                </div>
            @endif
        </dl>
    </div>
@endif
