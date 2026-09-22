{{-- Pannello — nuova scheda struttura/servizio per conto di un partner (spec §5.3).
     Una x-admin.card per ogni step del percorso partner, nello stesso ordine, e
     un solo selettore di lingua per tutti i testi tradotti della pagina. --}}
@php
    $t = 'admin-catalog.create.structure.';
    $whole = $rooms->wholeProperty;
    $checkboxes = 'flex flex-col gap-3';
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.catalog.index')">{{ __($t.'back') }}</x-admin.back-link>

    <x-admin.page-header :heading="__($t.'heading')" :sub="$sub">
        <x-slot:actions>
            <x-admin.button tone="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                {{ __('admin-catalog.create.submit') }}
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid items-start gap-3.5 lg:grid-cols-3">
        <div class="flex min-w-0 flex-col gap-3.5 lg:col-span-2">

            {{-- Selettore di lingua unico: i campi tradotti restano tutti nel DOM
                 e si nasconde quello della lingua non attiva, come in ArticleEdit. --}}
            <x-admin.card>
                <x-admin.tabs class="px-5" model="lang" :current="$lang" :items="[
                    ['key' => 'it', 'label' => __('admin-catalog.show.lang_it')],
                    ['key' => 'en', 'label' => __('admin-catalog.show.lang_en')],
                ]" />
                <p class="m-0 px-5 py-3.5 text-[13px] leading-normal text-gray-600">{{ __($t.'lang_help') }}</p>
            </x-admin.card>

            {{-- Step 0 + 1: categoria e tipologia --}}
            <x-admin.card :heading="__($t.'section_type')">
                <div class="flex flex-col gap-5 p-5">
                    <flux:radio.group wire:model="category" :label="__($t.'category')" :description="__($t.'category_help')" variant="segmented">
                        <flux:radio value="struttura" :label="__($t.'category_struttura')" />
                        <flux:radio value="servizi" :label="__($t.'category_servizi')" />
                    </flux:radio.group>
                    <flux:error name="category" />

                    <flux:radio.group wire:model.live="type" :label="__($t.'type')" class="!flex !flex-col !gap-3">
                        @foreach ($options['type'] as $slug => $label)
                            <flux:radio :value="$slug" :label="$label" wire:key="type-{{ $slug }}" />
                        @endforeach
                    </flux:radio.group>
                    <flux:error name="type" />
                </div>
            </x-admin.card>

            {{-- Step 2: nome --}}
            <x-admin.card :heading="__($t.'section_name')">
                <div class="flex flex-col gap-4 p-5">
                    @foreach (['it', 'en'] as $code)
                        <div wire:key="name-{{ $code }}" @class(['hidden' => $lang !== $code])>
                            <flux:input
                                wire:model="name.{{ $code }}"
                                :label="__($t.'name').' · '.__('admin-catalog.show.lang_'.$code)"
                                :description="__($t.'name_help')"
                                maxlength="110"
                            />
                        </div>
                    @endforeach
                </div>
            </x-admin.card>

            {{-- Step 3: luogo --}}
            <x-admin.card :heading="__($t.'section_location')">
                <div class="grid items-start gap-4 p-5 md:grid-cols-2">
                    <flux:input wire:model="location.address" :label="__($t.'address')" />
                    <flux:input wire:model="location.city" :label="__($t.'city')" />

                    {{-- Elenco dalla tabella provinces: il valore salvato è la sigla,
                         da cui il publisher ricava la regione. flux:field composto a
                         mano, quindi il flux:error va messo esplicitamente. --}}
                    <flux:field>
                        <flux:label>{{ __($t.'province') }}</flux:label>
                        <flux:select variant="listbox" searchable wire:model="location.province" :placeholder="__($t.'province')">
                            @foreach ($provinces as $province)
                                <flux:select.option value="{{ $province->short_name }}" wire:key="province-{{ $province->short_name }}">
                                    {{ $province->name }} ({{ $province->short_name }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="location.province" />
                        <flux:description>{{ __($t.'province_help') }}</flux:description>
                    </flux:field>

                    <flux:input wire:model="location.zip" :label="__($t.'zip')" inputmode="numeric" maxlength="5" />
                    <flux:input wire:model="location.license" :label="__($t.'license')" class="md:col-span-2" />
                </div>
            </x-admin.card>

            {{-- Step 4: descrizione --}}
            <x-admin.card :heading="__($t.'section_description')">
                <div class="flex flex-col gap-4 p-5">
                    @foreach (['it', 'en'] as $code)
                        <div wire:key="description-{{ $code }}" @class(['hidden' => $lang !== $code])>
                            <flux:textarea
                                wire:model="description.{{ $code }}"
                                :label="__($t.'description').' · '.__('admin-catalog.show.lang_'.$code)"
                                :description="__($t.'description_help')"
                                rows="3"
                                maxlength="200"
                                resize="vertical"
                            />
                        </div>
                    @endforeach
                </div>
            </x-admin.card>

            {{-- Step 5: camere (o alloggio intero per la casa vacanza) --}}
            <x-admin.card :heading="__($whole ? $t.'section_whole' : $t.'section_rooms')">
                <div class="flex flex-col gap-5 p-5">
                    @if ($whole)
                        <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __($t.'whole_help') }}</p>
                        <div class="grid items-start gap-4 md:grid-cols-2">
                            <flux:input type="number" min="1" max="50" wire:model="rooms.rooms.0.beds" :label="__($t.'beds')" />
                            <flux:input type="number" min="0" step="0.01" wire:model="rooms.rooms.0.price" :label="__($t.'room_price')" />
                        </div>
                    @else
                        <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __($t.'rooms_help') }}</p>

                        @foreach ($rooms->rooms as $i => $room)
                            <div class="grid items-start gap-4 md:grid-cols-[1fr_auto_1fr_auto]" wire:key="room-{{ $i }}">
                                <flux:select wire:model="rooms.rooms.{{ $i }}.type" :label="__($t.'room_type')" :placeholder="__($t.'room_type')">
                                    @foreach ($options['room'] as $slug => $label)
                                        <flux:select.option value="{{ $slug }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:field>
                                    <flux:label>{{ __($t.'room_count') }}</flux:label>
                                    <div class="flex h-10 items-center gap-2 rounded-[6px] border border-gray-300 px-1.5">
                                        <x-admin.button tone="ghost" icon="minus" square wire:click="decrementRoom({{ $i }})" class="!h-8 !w-8 !px-0" />
                                        <span class="min-w-6 text-center text-[14.5px] font-semibold text-admin-rail">{{ $room['count'] }}</span>
                                        <x-admin.button tone="ghost" icon="plus" square wire:click="incrementRoom({{ $i }})" class="!h-8 !w-8 !px-0" />
                                    </div>
                                    <flux:error name="rooms.rooms.{{ $i }}.count" />
                                </flux:field>

                                <flux:input type="number" min="0" step="0.01" wire:model="rooms.rooms.{{ $i }}.price" :label="__($t.'room_price')" />

                                <flux:field>
                                    <flux:label class="opacity-0">{{ __($t.'remove_room') }}</flux:label>
                                    <x-admin.button
                                        tone="danger"
                                        icon="trash"
                                        square
                                        wire:click="removeRoom({{ $i }})"
                                        :disabled="count($rooms->rooms) <= 1"
                                        :aria-label="__($t.'remove_room')"
                                        class="!h-10 !w-10 !px-0"
                                    />
                                </flux:field>
                            </div>
                        @endforeach

                        <flux:error name="rooms.rooms" />
                        <flux:error name="rooms.rooms.*.type" />

                        <x-admin.button tone="ghost" icon="plus" wire:click="addRoom" class="self-start">{{ __($t.'add_room') }}</x-admin.button>
                    @endif

                    <div class="grid items-start gap-4 md:grid-cols-4">
                        <flux:select wire:model="rooms.checkinFrom" :label="__($t.'checkin').' · '.__($t.'time_from')" placeholder="--:--">
                            @foreach ($times as $time)
                                <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="rooms.checkinTo" :label="__($t.'checkin').' · '.__($t.'time_to')" placeholder="--:--">
                            @foreach ($times as $time)
                                <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="rooms.checkoutFrom" :label="__($t.'checkout').' · '.__($t.'time_from')" placeholder="--:--">
                            @foreach ($times as $time)
                                <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="rooms.checkoutTo" :label="__($t.'checkout').' · '.__($t.'time_to')" placeholder="--:--">
                            @foreach ($times as $time)
                                <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </x-admin.card>

            {{-- Step 6: cancellazione --}}
            <x-admin.card :heading="__($t.'section_cancellation')">
                <div class="p-5">
                    <flux:select wire:model="cancellationWhen" :label="__($t.'cancellation')" class="max-w-[280px]">
                        @foreach ($cancellationOptions as $slug => $label)
                            <flux:select.option value="{{ $slug }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </x-admin.card>

            {{-- Step 7: servizi della struttura.
                 Ogni gruppo porta il proprio flux:error: le regole falliscono su
                 chiavi indicizzate (services.services.1) che nessun controllo
                 mostra, e senza questi il salvataggio si blocca in silenzio. --}}
            <x-admin.card :heading="__($t.'section_services')">
                <div class="flex flex-col gap-6 p-5">
                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model="services.services" :label="__($t.'services')" :class="$checkboxes">
                            @foreach ($options['services'] as $slug => $label)
                                <flux:checkbox :value="$slug" :label="$label" wire:key="svc-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="services.services" />
                        <flux:error name="services.services.*" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model="services.additional" :label="__($t.'additional')" :class="$checkboxes">
                            @foreach ($options['additional'] as $slug => $label)
                                <flux:checkbox :value="$slug" :label="$label" wire:key="add-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="services.additional" />
                        <flux:error name="services.additional.*" />
                    </div>

                    {{-- Gli orari restano sempre a video, senza wire:model.live sulle
                         caselle: il publisher usa solo quelli dei pasti selezionati
                         (FamilyPublisher::mealRows), e una pagina lunga non deve
                         rifarsi a ogni spunta. --}}
                    <div class="flex flex-col gap-3">
                        <p class="m-0 text-[13.5px] font-bold text-admin-rail">{{ __($t.'meal_times') }}</p>
                        <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __($t.'meal_times_help') }}</p>
                        @foreach (['colazione', 'pranzo', 'cena'] as $meal)
                            <div class="grid items-start gap-4 md:grid-cols-2" wire:key="meal-{{ $meal }}">
                                <flux:select wire:model="services.mealTimes.{{ $meal }}.from" :label="$options['additional'][$meal].' · '.__($t.'time_from')" placeholder="--:--">
                                    @foreach ($times as $time)
                                        <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="services.mealTimes.{{ $meal }}.to" :label="$options['additional'][$meal].' · '.__($t.'time_to')" placeholder="--:--">
                                    @foreach ($times as $time)
                                        <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        @endforeach
                    </div>

                    @foreach (['it', 'en'] as $code)
                        <div wire:key="additional-other-{{ $code }}" @class(['hidden' => $lang !== $code])>
                            <flux:textarea
                                wire:model="services.additionalOther.{{ $code }}"
                                :label="__($t.'other').' · '.__('admin-catalog.show.lang_'.$code)"
                                :description="__($t.'other_help')"
                                rows="2"
                                maxlength="200"
                                resize="vertical"
                            />
                        </div>
                    @endforeach

                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model="services.structureRules" :label="__($t.'structure_rules')" :class="$checkboxes">
                            @foreach ($options['rules'] as $slug => $label)
                                <flux:checkbox :value="$slug" :label="$label" wire:key="rule-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="services.structureRules" />
                        <flux:error name="services.structureRules.*" />
                    </div>
                </div>
            </x-admin.card>

            {{-- Step 8: servizi per gli animali --}}
            <x-admin.card :heading="__($t.'section_animal_services')">
                <div class="flex flex-col gap-6 p-5">
                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model="animalServices" :label="__($t.'animal_services')" :class="$checkboxes">
                            @foreach ($options['animal'] as $slug => $label)
                                <flux:checkbox :value="$slug" :label="$label" wire:key="animal-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="animalServices" />
                        <flux:error name="animalServices.*" />
                    </div>

                    @foreach (['it', 'en'] as $code)
                        <div wire:key="animal-other-{{ $code }}" @class(['hidden' => $lang !== $code])>
                            <flux:textarea
                                wire:model="animalServicesOther.{{ $code }}"
                                :label="__($t.'other').' · '.__('admin-catalog.show.lang_'.$code)"
                                :description="__($t.'other_help')"
                                rows="2"
                                maxlength="200"
                                resize="vertical"
                            />
                        </div>
                    @endforeach
                </div>
            </x-admin.card>

            {{-- Step 9: smartbox (gruppo smartbox_consent) --}}
            <x-admin.card :heading="__($t.'section_smartbox')">
                <div class="flex flex-col gap-5 p-5">
                    <flux:radio.group wire:model.live="smartboxConsent" :label="__($t.'smartbox_consent')" variant="segmented">
                        <flux:radio value="si" :label="__($t.'smartbox_yes')" />
                        <flux:radio value="no" :label="__($t.'smartbox_no')" />
                    </flux:radio.group>

                    @if ($smartboxConsent === 'si')
                        <div class="flex flex-col gap-2">
                            <flux:checkbox.group wire:model="smartboxTypes" :label="__($t.'smartbox_types')" :class="$checkboxes">
                                @foreach ($options['smartbox'] as $slug => $label)
                                    <flux:checkbox :value="$slug" :label="$label" wire:key="sbox-{{ $slug }}" />
                                @endforeach
                            </flux:checkbox.group>
                            <flux:error name="smartboxTypes" />
                            <flux:error name="smartboxTypes.*" />
                        </div>
                    @endif
                </div>
            </x-admin.card>

            {{-- Step 10: foto (dropzone come la copertina di ArticleEdit) --}}
            <x-admin.card :heading="__($t.'section_photos')">
                <div class="flex flex-col gap-3 p-5">
                    <flux:file-upload wire:model="photos" multiple accept="image/*" class="w-full">
                        <div class="flex min-h-[94px] w-full cursor-pointer flex-col items-center justify-center gap-1 rounded-lg bg-gray-100 px-4 py-4 text-center">
                            <span class="text-[14.5px] font-bold text-admin-teal">{{ __('admin-catalog.create.photos_help') }}</span>
                        </div>
                    </flux:file-upload>

                    <p wire:loading wire:target="photos" class="m-0 text-[12.5px] text-gray-400">{{ __('partner.hotel_photos.uploading') }}</p>
                    <flux:error name="photos" />
                    <flux:error name="photos.*" />
                    <p class="m-0 text-[12.5px] leading-normal text-gray-400">{{ __('partner.hotel_photos.hint') }}</p>

                    @if (filled($photos))
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                            @foreach ($photos as $i => $photo)
                                <div class="flex flex-col gap-1.5" wire:key="photo-{{ $i }}">
                                    @if (method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                        <img src="{{ $photo->temporaryUrl() }}" alt="" class="block h-[96px] w-full rounded-lg bg-gray-100 object-cover">
                                    @else
                                        <div class="h-[96px] w-full rounded-lg bg-gray-100"></div>
                                    @endif
                                    @if ($i === 0)
                                        <x-admin.badge tone="info">{{ __('admin-catalog.create.cover') }}</x-admin.badge>
                                    @endif
                                    <x-admin.button tone="ghost" icon="trash" wire:click="removePhoto({{ $i }})">{{ __('partner.hotel_photos.delete') }}</x-admin.button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-admin.card>

            {{-- Step 11: coordinate bancarie (facoltative, come lo "skip" del wizard) --}}
            <x-admin.card :heading="__($t.'section_payment')">
                <div class="flex flex-col gap-4 p-5">
                    <p class="m-0 text-[13px] leading-normal text-gray-600">{{ __($t.'payment_help') }}</p>
                    <div class="grid items-start gap-4 md:grid-cols-3">
                        <flux:input wire:model="payment.accountHolder" :label="__($t.'account_holder')" />
                        <flux:input wire:model="payment.iban" :label="__($t.'iban')" maxlength="34" />
                        <flux:input wire:model="payment.bic" :label="__($t.'bic')" maxlength="11" />
                    </div>
                </div>
            </x-admin.card>
        </div>

        @include('livewire.admin.catalog.partials.partner-aside')
    </div>
</div>
