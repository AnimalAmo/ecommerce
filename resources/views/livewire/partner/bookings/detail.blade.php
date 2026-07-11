{{-- Dettaglio prenotazione strutture (XD "Dettaglio prenotazione strutture") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <div class="mx-auto w-full max-w-[910px] rounded-[10px] border border-gray-150 bg-white px-8 py-7">

                {{-- Indietro + Stampa (XD: link freccia a sx, pill viola a dx) --}}
                <div class="flex items-center justify-between gap-4">
                    <flux:link href="{{ route('partner.bookings') }}" class="!inline-flex !items-center !gap-2 !text-[13px] !text-[#959595] !no-underline hover:!text-ink">
                        <flux:icon.arrow-back class="h-3.5 w-3.5" />
                        {{ __('partner.bookings.detail_back') }}
                    </flux:link>
                    <flux:button onclick="window.print()" class="!h-[28px] !gap-1.5 !rounded-[5px] !border-0 !bg-[#EFE5FF] !px-3 !text-[15px] !font-normal !text-[#904EF2] !shadow-none hover:!bg-[#904EF2] hover:!text-white [&>span]:flex [&>span]:items-center [&>span]:gap-1.5">
                        <flux:icon.printer class="!h-[14px] !w-[14px]" />
                        {{ __('partner.bookings.detail_print') }}
                    </flux:button>
                </div>

                {{-- Titolo + info cliente a sx, foto struttura a dx --}}
                <div class="mt-6 flex flex-col gap-8 sm:flex-row sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <h1 class="text-[25px] font-bold leading-[30px] text-[#0D171A]">{{ $info['right']['detail_structure'] }}</h1>

                        <h2 class="mt-[13px] text-lg font-semibold text-[#0D171A]">{{ __('partner.bookings.detail_customer') }}</h2>
                        <dl class="mt-4 space-y-[13px]">
                            @foreach (['detail_first_name' => $customer['first_name'], 'detail_last_name' => $customer['last_name'], 'detail_email' => $customer['email'], 'detail_phone' => $customer['phone']] as $label => $value)
                                <div class="flex items-baseline gap-4 pl-4">
                                    <dt class="w-[195px] shrink-0 text-[15px] font-semibold text-[#627277]">{{ __('partner.bookings.'.$label) }}</dt>
                                    <dd class="text-[15px] font-medium text-[#0D171A]">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <img src="{{ asset('img/booking-detail-hotel.jpg') }}" alt="{{ $info['right']['detail_structure'] }}" class="h-[184px] w-[277px] shrink-0 rounded-[4px] object-cover">
                </div>

                <div class="mt-6 border-t border-[#E2EAEB]" aria-hidden="true"></div>

                {{-- Info prenotazione: due colonne label/valore come nel mockup --}}
                <h2 class="mt-[35px] text-lg font-semibold text-[#0D171A]">{{ __('partner.bookings.detail_booking') }}</h2>
                <div class="mt-4 grid grid-cols-1 gap-x-10 gap-y-[13px] sm:grid-cols-2">
                    <dl class="space-y-[13px]">
                        @foreach ($info['left'] as $label => $value)
                            <div class="flex items-baseline gap-4 pl-4">
                                <dt class="w-[195px] shrink-0 text-[15px] font-semibold text-[#627277]">{{ __('partner.bookings.'.$label) }}</dt>
                                <dd class="text-[15px] font-medium text-[#0D171A]">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <dl class="space-y-[13px]">
                        @foreach ($info['right'] as $label => $value)
                            <div class="flex items-baseline gap-4 pl-4">
                                <dt class="w-[125px] shrink-0 text-[15px] font-semibold text-[#627277]">{{ __('partner.bookings.'.$label) }}</dt>
                                <dd class="text-[15px] font-medium text-[#0D171A]">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
