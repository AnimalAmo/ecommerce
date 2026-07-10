{{-- Dettaglio servizio struttura (XD "Dettaglio prenotazione strutture – dettagli") --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $none = __('partner.services.not_provided');
    $typeLabels = ['hotel' => 'Hotel', 'bb' => 'B&B', 'agriturismo' => 'Agriturismo'];
    $join = fn ($arr) => filled($arr) ? implode(', ', (array) $arr) : $none;
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <div class="mx-auto w-full max-w-[910px]">

                <div class="flex items-center justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ $draft->name ?: $none }}</h1>
                    <flux:link href="{{ route('partner.services') }}" class="!text-[15px] !font-bold !text-brand-cyan !decoration-current hover:!text-[#4FB9DB]">{{ __('partner.services.back') }}</flux:link>
                </div>

                <div class="mt-8 divide-y divide-gray-150 rounded-[10px] border border-gray-150 bg-white">
                    @php
                        $sections = [
                            'section_type' => $typeLabels[$draft->type] ?? ($draft->type ?: $none),
                            'section_name' => $draft->name ?: $none,
                            'section_location' => collect([$draft->address, trim($draft->city.' '.($draft->province ? '('.$draft->province.')' : '')), $draft->zip, $draft->license])->filter()->implode(' · ') ?: $none,
                            'section_description' => $draft->description ?: $none,
                        ];
                    @endphp

                    @foreach ($sections as $key => $value)
                        <div class="px-6 py-5">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-[#959595]">{{ __('partner.services.'.$key) }}</h2>
                            <p class="mt-2 text-[15px] leading-relaxed text-[#0D171A]">{{ $value }}</p>
                        </div>
                    @endforeach

                    {{-- Stanze --}}
                    <div class="px-6 py-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-[#959595]">{{ __('partner.services.section_rooms') }}</h2>
                        @if (filled($draft->rooms))
                            <ul class="mt-2 space-y-1 text-[15px] text-[#0D171A]">
                                @foreach ($draft->rooms as $room)
                                    <li>{{ $room['type'] ?? '' }} — {{ $room['count'] ?? 0 }} {{ __('partner.services.rooms_count') }} · €{{ $room['price'] ?? '' }} {{ __('partner.services.rooms_price') }}</li>
                                @endforeach
                            </ul>
                            <p class="mt-2 text-sm text-[#627277]">{{ __('partner.services.checkin') }}: {{ $draft->checkin_from ?: '—' }}–{{ $draft->checkin_to ?: '—' }} · {{ __('partner.services.checkout') }}: {{ $draft->checkout_from ?: '—' }}–{{ $draft->checkout_to ?: '—' }}</p>
                        @else
                            <p class="mt-2 text-[15px] text-[#0D171A]">{{ $none }}</p>
                        @endif
                    </div>

                    {{-- Cancellazione --}}
                    <div class="px-6 py-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-[#959595]">{{ __('partner.services.section_cancellation') }}</h2>
                        <p class="mt-2 text-[15px] text-[#0D171A]">
                            @if ($draft->cancellation_when === '1')
                                {{ __('partner.services.cancellation_day') }}
                            @elseif ($draft->cancellation_when)
                                {{ __('partner.services.cancellation_days', ['days' => $draft->cancellation_when]) }}
                            @else
                                {{ $none }}
                            @endif
                        </p>
                    </div>

                    {{-- Servizi --}}
                    <div class="px-6 py-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-[#959595]">{{ __('partner.services.section_services') }}</h2>
                        <p class="mt-2 text-[15px] text-[#0D171A]">{{ $join(array_merge((array) $draft->services, (array) $draft->additional_services, (array) $draft->rules)) }}</p>
                    </div>

                    {{-- Informazioni aggiuntive (servizi animali) --}}
                    <div class="px-6 py-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-[#959595]">{{ __('partner.services.section_extra') }}</h2>
                        <p class="mt-2 text-[15px] text-[#0D171A]">{{ $join(array_filter(array_merge((array) $draft->animal_services, [$draft->animal_services_other]))) }}</p>
                    </div>

                    {{-- Foto --}}
                    <div class="px-6 py-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-[#959595]">{{ __('partner.services.section_photos') }}</h2>
                        @if (filled($draft->photos))
                            <div class="mt-3 flex flex-wrap gap-3">
                                @foreach ($draft->photos as $photo)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}" alt="" class="h-[90px] w-[120px] rounded-[6px] object-cover">
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-[15px] text-[#0D171A]">{{ $none }}</p>
                        @endif
                    </div>

                    {{-- Metodo di pagamento --}}
                    <div class="px-6 py-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-[#959595]">{{ __('partner.services.section_payment') }}</h2>
                        <p class="mt-2 text-[15px] text-[#0D171A]">
                            {{ $draft->account_holder ?: $none }}@if ($draft->iban) · {{ $draft->iban }}@endif@if ($draft->bic) · {{ $draft->bic }}@endif@if ($draft->sdi) · SDI {{ $draft->sdi }}@endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
