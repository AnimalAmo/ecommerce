{{-- ============ MODAL FILTRI 2 (solo mobile, XD app "Filtri 2 ricerca") ============ --}}
@php
    // Card tipologia XD: 156x100, r3, bordo 0.5 #C8C8C8; selezionata bg #EBF9FD
    $filterCard = '!flex !h-[100px] !w-full !whitespace-normal !rounded-[3px] !p-3 !shadow-none [&>span]:!flex [&>span]:!h-full [&>span]:!w-full [&>span]:!flex-col [&>span]:!items-start [&>span]:!justify-between';
    // Selezionata: fondo #EBF9FD e nessun bordo (XD "Filtri 2 ricerca - click su 'mostra risultati' - 3":
    // la card accesa ha fill solid e stroke none, quella spenta fill none e stroke #C8C8C8 0.5).
    $filterCardOn = '!border-0 !bg-[#EBF9FD]';
    $filterCardOff = '!border !border-[#C8C8C8] !bg-white';
    // Doppio slider prezzo: input sovrapposti, solo i pallini (24px, bianchi, bordo #E9E9E9) ricevono il tocco
    $rangeInput = '!absolute !inset-0 !h-6 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-6 [&_input]:!appearance-none [&_input]:!bg-transparent [&_input]:!border-0 [&_input]:!rounded-none [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:!outline-none [&_input]:!pointer-events-none [&_input::-webkit-slider-thumb]:pointer-events-auto [&_input::-webkit-slider-thumb]:h-6 [&_input::-webkit-slider-thumb]:w-6 [&_input::-webkit-slider-thumb]:appearance-none [&_input::-webkit-slider-thumb]:rounded-full [&_input::-webkit-slider-thumb]:border [&_input::-webkit-slider-thumb]:border-[#E9E9E9] [&_input::-webkit-slider-thumb]:bg-white [&_input::-moz-range-thumb]:pointer-events-auto [&_input::-moz-range-thumb]:h-6 [&_input::-moz-range-thumb]:w-6 [&_input::-moz-range-thumb]:appearance-none [&_input::-moz-range-thumb]:rounded-full [&_input::-moz-range-thumb]:border [&_input::-moz-range-thumb]:border-[#E9E9E9] [&_input::-moz-range-thumb]:bg-white';
    $priceBox = 'flex h-[50px] w-[112px] flex-col justify-center rounded-[3px] border border-[#C8C8C8] px-3';
    // "9 €" compatto come nel mock: larghezza dinamica via Alpine (x-bind:style sul wrapper), niente chrome né spinner
    $priceInput = '!w-auto !h-5 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-5 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!leading-5 [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:[-moz-appearance:textfield] [&_input::-webkit-inner-spin-button]:appearance-none [&_input::-webkit-outer-spin-button]:appearance-none';
    $checkbox = '[--color-accent:var(--color-brand-cyan)] [--color-accent-foreground:#fff] [&_[data-flux-checkbox-indicator]]:size-5 [&_[data-flux-checkbox-indicator]]:rounded-full [&_[data-flux-checkbox-indicator]]:border-brand-cyan';
@endphp
{{-- min+max h-dvh: pannello a tutto schermo che scrolla al suo interno (contenuto XD fino a 1353px) --}}
<flux:modal name="mobile-filters" :closable="false" class="w-full !m-0 !max-w-full !min-h-dvh !max-h-dvh !overflow-y-auto !rounded-none bg-white !px-6 !py-6 lg:hidden">
    <div class="relative flex items-center justify-center py-1">
        <h2 class="text-lg font-semibold text-[#0D171A]">{{ __('catalog.filters_title') }}</h2>
        <div class="absolute right-0 top-1/2 -translate-y-1/2">
            <flux:modal.close>
                <flux:button variant="ghost" size="sm" square aria-label="{{ __('nav.menu_close') }}" class="!rounded-full !text-[#555555]">
                    <flux:icon.close class="h-4 w-4" />
                </flux:button>
            </flux:modal.close>
        </div>
    </div>

    {{-- Fascia di prezzo (XD: istogramma cyan/#EBF9FD + doppio cursore + box Minimo/Massimo) --}}
    <div class="mt-4 border-t border-[#E9E9E9] pt-7">
        <h3 class="whitespace-nowrap text-lg font-semibold text-[#0D171A]">{{ __('catalog.filter_price') }}</h3>

        <div x-data="{
                floor: {{ $priceFloor }},
                ceil: {{ $priceCeil }},
                lo: {{ $priceMin }}, hi: {{ $priceMax }},
                bars: [7, 12, 22, 33, 62, 47, 80, 70, 55, 42, 67, 55, 42, 34, 24, 14, 11, 7],
                pct(v) { return (v - this.floor) / (this.ceil - this.floor) * 100 },
                barOn(i) { const c = (i + 0.5) / this.bars.length * 100; return c >= this.pct(Math.min(this.lo, this.hi)) && c <= this.pct(Math.max(this.lo, this.hi)) },
                {{-- assegnazione diretta = deferred; $set forza il roundtrip (porta con sé anche priceMin) --}}
                push() { $wire.priceMin = Math.min(this.lo, this.hi); $wire.$set('priceMax', Math.max(this.lo, this.hi)) },
             }"
             x-effect="lo = $wire.priceMin; hi = $wire.priceMax"
             class="mt-6">
            {{-- Istogramma decorativo (altezze dal mock): barre nella fascia in cyan, fuori #EBF9FD --}}
            <div class="flex h-20 items-end justify-center gap-[3px]" aria-hidden="true">
                <template x-for="(bar, i) in bars" :key="i">
                    <div class="w-[7px]" x-bind:class="barOn(i) ? 'bg-brand-cyan' : 'bg-[#EBF9FD]'" x-bind:style="`height:${bar}px`"></div>
                </template>
            </div>
            {{-- -mt-3: i pallini stanno centrati SULLA baseline dell'istogramma (XD y256≈fondo barre) --}}
            <div class="relative -mt-3 h-6">
                <div class="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-[#EBF9FD]"></div>
                <div class="absolute top-1/2 h-px -translate-y-1/2 bg-brand-cyan" x-bind:style="`left:${pct(Math.min(lo, hi))}%; right:${100 - pct(Math.max(lo, hi))}%`"></div>
                <flux:label class="sr-only" for="filter-price-lo">{{ __('catalog.filter_price_min') }}</flux:label>
                <flux:input id="filter-price-lo" type="range" min="{{ $priceFloor }}" max="{{ $priceCeil }}" x-model.number="lo" x-on:change="push" class="{{ $rangeInput }}" />
                <flux:label class="sr-only" for="filter-price-hi">{{ __('catalog.filter_price_max') }}</flux:label>
                <flux:input id="filter-price-hi" type="range" min="{{ $priceFloor }}" max="{{ $priceCeil }}" x-model.number="hi" x-on:change="push" class="{{ $rangeInput }}" />
            </div>

            {{-- Box Minimo/Massimo (XD: 112x50, r3, bordo #C8C8C8, label 11 light, valore 14 + €) --}}
            <div class="mt-5 flex items-center justify-between gap-4">
                <label class="{{ $priceBox }}">
                    <span class="text-[11px] font-light text-[#555555]">{{ __('catalog.filter_price_min') }}</span>
                    <span class="flex items-center gap-1 text-sm text-[#0D171A]">
                        <flux:input type="number" min="{{ $priceFloor }}" max="{{ $priceCeil }}" x-model.number="lo" x-bind:style="`width:${String(lo).length * 9 + 4}px`" x-on:change="push" class="{{ $priceInput }}" />
                        €
                    </span>
                </label>
                <label class="{{ $priceBox }}">
                    <span class="text-[11px] font-light text-[#555555]">{{ __('catalog.filter_price_max') }}</span>
                    <span class="flex items-center gap-1 text-sm text-[#0D171A]">
                        <flux:input type="number" min="{{ $priceFloor }}" max="{{ $priceCeil }}" x-model.number="hi" x-bind:style="`width:${String(hi).length * 9 + 4}px`" x-on:change="push" class="{{ $priceInput }}" />
                        €
                    </span>
                </label>
            </div>
        </div>
    </div>

    {{-- Tipologia (XD: griglia 2 colonne, card icona+label; selezionata bg #EBF9FD e testo cyan) --}}
    <div class="mt-7 border-t border-[#E9E9E9] pt-7">
        <h3 class="whitespace-nowrap text-lg font-semibold text-[#0D171A]">{{ __('catalog.filter_type') }}</h3>
        <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4">
            <flux:button wire:click="toggleType('hotel')" class="{{ $filterCard }} {{ in_array('hotel', $activeTypes, true) ? $filterCardOn : $filterCardOff }}">
                <flux:icon.structure-bb class="h-8 w-8 {{ in_array('hotel', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                <span class="text-left text-[15px] font-medium {{ in_array('hotel', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.filter_types.hotel') }}</span>
            </flux:button>
            <flux:button wire:click="toggleType('servizi')" class="{{ $filterCard }} {{ in_array('servizi', $activeTypes, true) ? $filterCardOn : $filterCardOff }}">
                <flux:icon.animal class="h-8 w-8 {{ in_array('servizi', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                <span class="text-left text-[15px] font-medium {{ in_array('servizi', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.filter_types.servizi') }}</span>
            </flux:button>
            <flux:button wire:click="toggleType('attivita')" class="{{ $filterCard }} {{ in_array('attivita', $activeTypes, true) ? $filterCardOn : $filterCardOff }}">
                <flux:icon.activity-balloons class="h-8 w-8 {{ in_array('attivita', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                <span class="text-left text-[15px] font-medium {{ in_array('attivita', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.filter_types.attivita') }}</span>
            </flux:button>
            <flux:button wire:click="toggleType('eventi')" class="{{ $filterCard }} {{ in_array('eventi', $activeTypes, true) ? $filterCardOn : $filterCardOff }}">
                <flux:icon.activity-navigation class="h-8 w-8 {{ in_array('eventi', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                <span class="text-left text-[15px] font-medium {{ in_array('eventi', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.filter_types.eventi') }}</span>
            </flux:button>
            <flux:button wire:click="toggleType('smartbox')" class="{{ $filterCard }} {{ in_array('smartbox', $activeTypes, true) ? $filterCardOn : $filterCardOff }}">
                <flux:icon.smartbox-gift class="h-8 w-8 {{ in_array('smartbox', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                <span class="text-left text-[15px] font-medium {{ in_array('smartbox', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.filter_types.smartbox') }}</span>
            </flux:button>
        </div>
    </div>

    {{-- Sezioni Smartbox (XD "Filtri 2 ricerca - click su 'soggiorno'": compaiono con Smartbox attivo) --}}
    @if (in_array('smartbox', $activeTypes, true))
        <div class="mt-7 border-t border-[#E9E9E9] pt-7">
            <h3 class="whitespace-nowrap text-lg font-semibold text-[#0D171A]">{{ __('catalog.smartbox_type_title') }}</h3>
            <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4">
                <flux:button wire:click="toggleSmartboxType('soggiorno')" class="{{ $filterCard }} {{ in_array('soggiorno', $smartboxTypes, true) ? $filterCardOn : $filterCardOff }}">
                    <flux:icon.structure-bb class="h-8 w-8 {{ in_array('soggiorno', $smartboxTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                    <span class="text-left">
                        {{-- XD: due righe con stili diversi — la tipologia 15 medium, "Smartbox" 14 regular grigio --}}
                        <span class="block text-[15px] font-medium {{ in_array('soggiorno', $smartboxTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.smartbox_chips.soggiorno') }}</span>
                        <span class="block text-sm font-normal text-[#959595]">{{ __('catalog.filter_types.smartbox') }}</span>
                    </span>
                </flux:button>
                <flux:button wire:click="toggleSmartboxType('benessere')" class="{{ $filterCard }} {{ in_array('benessere', $smartboxTypes, true) ? $filterCardOn : $filterCardOff }}">
                    <flux:icon.smartbox-wellness class="h-8 w-8 {{ in_array('benessere', $smartboxTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                    <span class="text-left">
                        {{-- XD: due righe con stili diversi — la tipologia 15 medium, "Smartbox" 14 regular grigio --}}
                        <span class="block text-[15px] font-medium {{ in_array('benessere', $smartboxTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.smartbox_chips.benessere') }}</span>
                        <span class="block text-sm font-normal text-[#959595]">{{ __('catalog.filter_types.smartbox') }}</span>
                    </span>
                </flux:button>
                <flux:button wire:click="toggleSmartboxType('avventura')" class="{{ $filterCard }} {{ in_array('avventura', $smartboxTypes, true) ? $filterCardOn : $filterCardOff }}">
                    <flux:icon.smartbox-adventure class="h-8 w-8 {{ in_array('avventura', $smartboxTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                    <span class="text-left">
                        {{-- XD: due righe con stili diversi — la tipologia 15 medium, "Smartbox" 14 regular grigio --}}
                        <span class="block text-[15px] font-medium {{ in_array('avventura', $smartboxTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.smartbox_chips.avventura') }}</span>
                        <span class="block text-sm font-normal text-[#959595]">{{ __('catalog.filter_types.smartbox') }}</span>
                    </span>
                </flux:button>
            </div>
        </div>

        <div class="mt-7">
            <h3 class="whitespace-nowrap text-lg font-semibold text-[#0D171A]">{{ __('catalog.people_title') }}</h3>
            <flux:checkbox.group wire:model.live="peopleGroups" class="mt-4 flex flex-col gap-4">
                @foreach (['coppia', 'famiglia', 'gruppo'] as $group)
                    <label wire:key="people-{{ $group }}" class="flex items-center gap-3">
                        <flux:checkbox value="{{ $group }}" class="{{ $checkbox }}" />
                        <span class="text-left text-[15px] font-medium text-[#0D171A]">{{ __('catalog.people_groups.'.$group) }}</span>
                    </label>
                @endforeach
            </flux:checkbox.group>
        </div>
    @endif

    <flux:modal.close>
        <flux:button class="mx-auto mt-10 !flex !h-[39px] !rounded-full !bg-[#0D171A] !px-8 !text-sm !font-bold !text-white hover:!bg-black">{{ __('catalog.show_results', ['count' => $resultsCount]) }}</flux:button>
    </flux:modal.close>
</flux:modal>
