{{--
    Dicitura "pagamento in struttura" delle schede dettaglio: il partner non
    chiede il pagamento online, quindi il totale mostrato si salda a lui.
    Solo sulle schede e mai sulle card delle liste, dove costerebbe una query
    per card. $noticeClass (facoltativa) regola il margine dal blocco sopra.
--}}
<p class="{{ $noticeClass ?? '' }} flex items-start gap-2 text-sm leading-[19px] text-[#627277]">
    <flux:icon.pin class="mt-[2px] h-4 w-4 shrink-0" />
    <span>{{ __('catalog.pay_on_site') }}</span>
</p>
