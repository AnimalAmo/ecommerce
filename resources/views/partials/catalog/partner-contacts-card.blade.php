{{--
    Prende il posto del box prenotazione sulle schede dei partner che non
    usano il pagamento online (richiesta della cliente, 29/09/2026): la scheda
    si consulta e il partner si contatta, senza passare dal checkout.

    $contacts arriva da App\Services\Partner\PartnerContacts e può essere null:
    di un partner senza profilo non sappiamo nemmeno la ragione sociale, e in
    quel caso resta la sola dicitura.
--}}
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
        </dl>

        @if (! empty($contacts['website']))
            <flux:button href="{{ $contacts['website'] }}" target="_blank" rel="noopener noreferrer" class="mt-6 !flex !h-[39px] w-full items-center justify-center !rounded-full !border-0 !bg-brand-yellow !px-0 text-sm !font-bold !text-[#0D171A] !shadow-none">
                {{ __('checkout.on_site.pay_on_website') }}
            </flux:button>
        @endif
    @endif
</div>
