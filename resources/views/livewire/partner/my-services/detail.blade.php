{{-- Dettaglio servizio struttura (XD "Dettaglio prenotazione strutture – dettagli"): card con
     Indietro + tag tipologia + titolo, e le 10 voci come righe accordion (label + caret + divider). --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $none = __('partner.services.not_provided');
    $typeLabels = ['hotel' => 'Hotel', 'bb' => 'B&B', 'agriturismo' => 'Agriturismo'];
    $typeTag = ['struttura' => 'Holiday', 'attivita' => 'Eventi', 'smartbox' => 'Smartbox'][$draft->family()] ?? '';
    $join = fn ($arr) => filled($arr) ? implode(', ', (array) $arr) : $none;

    // Voci accordion: label XD => contenuto già valorizzato (render sotto).
    $sections = [
        'section_type' => $typeLabels[$draft->type] ?? ($draft->type ?: $none),
        'section_name' => $draft->name ?: $none,
        'section_location' => collect([$draft->address, trim($draft->city.' '.($draft->province ? '('.$draft->province.')' : '')), $draft->zip, $draft->license])->filter()->implode(' · ') ?: $none,
        'section_description' => $draft->description ?: $none,
    ];
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <div class="mx-auto w-full max-w-[910px] rounded-[10px] border border-gray-150 bg-white px-8 py-7">

                {{-- Indietro (XD: link con freccia, in alto nella card) --}}
                <flux:link href="{{ route('partner.services') }}" class="!inline-flex !items-center !gap-2 !text-[15px] !font-bold !text-[#959595] !no-underline hover:!text-ink">
                    <flux:icon.arrow-back class="h-4 w-4" />
                    {{ __('partner.services.back') }}
                </flux:link>

                {{-- Tag tipologia + titolo --}}
                <div class="mt-5">
                    <flux:badge class="!rounded-[3px] !bg-brand-magenta !text-white">{{ $typeTag }}</flux:badge>
                </div>
                <h1 class="mt-3 text-[25px] font-bold text-[#0D171A]">{{ $draft->name ?: $none }}</h1>

                {{-- Righe accordion (XD: label Nunito-Medium 15 #2B2B2B + caret dx + divider #E2EAEB) --}}
                <div class="mt-6">
                    {{-- Le prime 4 voci hanno un valore testuale semplice --}}
                    @foreach ($sections as $key => $value)
                        <div x-data="{ open: false }" class="border-b border-[#E2EAEB]">
                            <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between px-4 py-[17px] text-left">
                                <span class="text-[15px] font-medium text-[#2B2B2B]">{{ __('partner.services.'.$key) }}</span>
                                <flux:icon.chevron-right class="h-4 w-4 shrink-0 text-[#0D171A] transition-transform" x-bind:class="open && 'rotate-90'" />
                            </button>
                            <div x-show="open" x-collapse style="display: none" class="px-4 pb-4">
                                <p class="text-[15px] leading-relaxed text-[#627277]">{{ $value }}</p>
                            </div>
                        </div>
                    @endforeach

                    {{-- Informazioni sulle stanze --}}
                    <div x-data="{ open: false }" class="border-b border-[#E2EAEB]">
                        <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between px-4 py-[17px] text-left">
                            <span class="text-[15px] font-medium text-[#2B2B2B]">{{ __('partner.services.section_rooms') }}</span>
                            <flux:icon.chevron-right class="h-4 w-4 shrink-0 text-[#0D171A] transition-transform" x-bind:class="open && 'rotate-90'" />
                        </button>
                        <div x-show="open" x-collapse style="display: none" class="px-4 pb-4">
                            @if (filled($draft->rooms))
                                <ul class="space-y-1 text-[15px] text-[#627277]">
                                    @foreach ($draft->rooms as $room)
                                        <li>{{ $room['type'] ?? '' }} — {{ $room['count'] ?? 0 }} {{ __('partner.services.rooms_count') }} · €{{ $room['price'] ?? '' }} {{ __('partner.services.rooms_price') }}</li>
                                    @endforeach
                                </ul>
                                <p class="mt-2 text-sm text-[#959595]">{{ __('partner.services.checkin') }}: {{ $draft->checkin_from ?: '—' }}–{{ $draft->checkin_to ?: '—' }} · {{ __('partner.services.checkout') }}: {{ $draft->checkout_from ?: '—' }}–{{ $draft->checkout_to ?: '—' }}</p>
                            @else
                                <p class="text-[15px] text-[#627277]">{{ $none }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Cancellazione --}}
                    <div x-data="{ open: false }" class="border-b border-[#E2EAEB]">
                        <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between px-4 py-[17px] text-left">
                            <span class="text-[15px] font-medium text-[#2B2B2B]">{{ __('partner.services.section_cancellation') }}</span>
                            <flux:icon.chevron-right class="h-4 w-4 shrink-0 text-[#0D171A] transition-transform" x-bind:class="open && 'rotate-90'" />
                        </button>
                        <div x-show="open" x-collapse style="display: none" class="px-4 pb-4">
                            <p class="text-[15px] text-[#627277]">
                                @if ($draft->cancellation_when === '1')
                                    {{ __('partner.services.cancellation_day') }}
                                @elseif ($draft->cancellation_when)
                                    {{ __('partner.services.cancellation_days', ['days' => $draft->cancellation_when]) }}
                                @else
                                    {{ $none }}
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Servizi --}}
                    <div x-data="{ open: false }" class="border-b border-[#E2EAEB]">
                        <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between px-4 py-[17px] text-left">
                            <span class="text-[15px] font-medium text-[#2B2B2B]">{{ __('partner.services.section_services') }}</span>
                            <flux:icon.chevron-right class="h-4 w-4 shrink-0 text-[#0D171A] transition-transform" x-bind:class="open && 'rotate-90'" />
                        </button>
                        <div x-show="open" x-collapse style="display: none" class="px-4 pb-4">
                            <p class="text-[15px] text-[#627277]">{{ $join(array_merge((array) $draft->services, (array) $draft->additional_services, (array) $draft->rules)) }}</p>
                        </div>
                    </div>

                    {{-- Informazioni aggiuntive (servizi animali) --}}
                    <div x-data="{ open: false }" class="border-b border-[#E2EAEB]">
                        <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between px-4 py-[17px] text-left">
                            <span class="text-[15px] font-medium text-[#2B2B2B]">{{ __('partner.services.section_extra') }}</span>
                            <flux:icon.chevron-right class="h-4 w-4 shrink-0 text-[#0D171A] transition-transform" x-bind:class="open && 'rotate-90'" />
                        </button>
                        <div x-show="open" x-collapse style="display: none" class="px-4 pb-4">
                            <p class="text-[15px] text-[#627277]">{{ $join(array_filter(array_merge((array) $draft->animal_services, [$draft->animal_services_other]))) }}</p>
                        </div>
                    </div>

                    {{-- Foto --}}
                    <div x-data="{ open: false }" class="border-b border-[#E2EAEB]">
                        <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between px-4 py-[17px] text-left">
                            <span class="text-[15px] font-medium text-[#2B2B2B]">{{ __('partner.services.section_photos') }}</span>
                            <flux:icon.chevron-right class="h-4 w-4 shrink-0 text-[#0D171A] transition-transform" x-bind:class="open && 'rotate-90'" />
                        </button>
                        <div x-show="open" x-collapse style="display: none" class="px-4 pb-4">
                            @if (filled($draft->photos))
                                <div class="flex flex-wrap gap-3">
                                    @foreach ($draft->photos as $photo)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}" alt="" class="h-[90px] w-[120px] object-cover">
                                    @endforeach
                                </div>
                            @else
                                <p class="text-[15px] text-[#627277]">{{ $none }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Metodo di pagamento --}}
                    <div x-data="{ open: false }" class="border-b border-[#E2EAEB]">
                        <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between px-4 py-[17px] text-left">
                            <span class="text-[15px] font-medium text-[#2B2B2B]">{{ __('partner.services.section_payment') }}</span>
                            <flux:icon.chevron-right class="h-4 w-4 shrink-0 text-[#0D171A] transition-transform" x-bind:class="open && 'rotate-90'" />
                        </button>
                        <div x-show="open" x-collapse style="display: none" class="px-4 pb-4">
                            <p class="text-[15px] text-[#627277]">
                                {{ $draft->account_holder ?: $none }}@if ($draft->iban) · {{ $draft->iban }}@endif@if ($draft->bic) · {{ $draft->bic }}@endif@if ($draft->sdi) · SDI {{ $draft->sdi }}@endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
