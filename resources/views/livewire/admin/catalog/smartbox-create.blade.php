{{-- Pannello — nuova smartbox per conto di un partner (spec §5.2). --}}
@php
    $checkboxes = '[--color-accent:#6CD1EF] [&_[data-flux-checkbox]]:!rounded-full [&_[data-flux-checkbox]_*]:!rounded-full';
    $selectedMeals = array_values(array_intersect(['colazione', 'pranzo', 'cena'], $meals->meals));
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.catalog.index')">{{ __('admin-catalog.create.smartbox.back') }}</x-admin.back-link>

    <x-admin.page-header :heading="__('admin-catalog.create.smartbox.heading')" :sub="__('admin-catalog.create.smartbox.sub')">
        <x-slot:actions>
            <x-admin.button tone="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ __('admin-catalog.create.submit') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid items-start gap-3.5 lg:grid-cols-3">
        <div class="flex min-w-0 flex-col gap-3.5 lg:col-span-2">

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_type')">
                <div class="p-5">
                    <flux:radio.group wire:model="type" variant="cards" class="[--color-accent:#6CD1EF]">
                        <flux:radio value="soggiorno" :label="__('partner.smartbox_type.soggiorno')" />
                        <flux:radio value="benessere" :label="__('partner.smartbox_type.benessere')" />
                        <flux:radio value="avventura" :label="__('partner.smartbox_type.avventura')" />
                    </flux:radio.group>
                    <flux:error name="type" />
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_texts')">
                <x-admin.tabs class="px-5" model="lang" :current="$lang" :items="[
                    ['key' => 'it', 'label' => __('admin-content.common.lang_it')],
                    ['key' => 'en', 'label' => __('admin-content.common.lang_en')],
                ]" />
                <div class="p-5">
                    @foreach (['it', 'en'] as $locale)
                        <div wire:key="smartbox-texts-{{ $locale }}" @class(['flex flex-col gap-4', 'hidden' => $lang !== $locale])>
                            <flux:input wire:model="name.{{ $locale }}" maxlength="110" :label="__('admin-catalog.create.smartbox.field_name')" />
                            <flux:textarea wire:model="description.{{ $locale }}" rows="3" maxlength="200" :label="__('admin-catalog.create.smartbox.field_description')" />
                            <flux:textarea wire:model="detailedDescription.{{ $locale }}" rows="5" maxlength="200" :label="__('admin-catalog.create.smartbox.field_detailed_description')" />
                        </div>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_duration')">
                <div class="p-5">
                    <flux:input type="number" min="1" max="365" wire:model="durationDays" class="max-w-[200px]" :label="__('admin-catalog.create.smartbox.field_duration')" :description="__('admin-catalog.create.smartbox.duration_help')" />
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_meals')">
                <div class="flex flex-col gap-5 p-5 {{ $checkboxes }}">
                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model.live="meals.meals" :label="__('admin-catalog.create.smartbox.field_meals')">
                            @foreach ($mealOptions as $slug => $label)
                                <flux:checkbox value="{{ $slug }}" :label="$label" wire:key="sb-meal-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="meals.meals" />
                        <flux:error name="meals.meals.*" />
                    </div>

                    @foreach ($selectedMeals as $meal)
                        <div wire:key="smartbox-meal-{{ $meal }}" class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:select wire:model="meals.mealTimes.{{ $meal }}.from" :label="$mealOptions[$meal].' — '.__('partner.smartbox_meals.time_from')" placeholder="--:--">
                                @foreach ($times as $time)
                                    <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="meals.mealTimes.{{ $meal }}.to" :label="$mealOptions[$meal].' — '.__('partner.smartbox_meals.time_to')" placeholder="--:--">
                                @foreach ($times as $time)
                                    <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endforeach

                    @if ($selectedMeals !== [])
                        <div class="flex flex-col gap-2">
                            <flux:checkbox.group wire:model="meals.dietary" :label="__('partner.smartbox_meals.dietary_heading')">
                                @foreach ($dietaryOptions as $slug => $label)
                                    <flux:checkbox value="{{ $slug }}" :label="$label" wire:key="sb-diet-{{ $slug }}" />
                                @endforeach
                            </flux:checkbox.group>
                            <flux:error name="meals.dietary" />
                            <flux:error name="meals.dietary.*" />
                        </div>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_offers')">
                <div class="flex flex-col gap-5 p-5 {{ $checkboxes }}">
                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model="offers" :label="__('admin-catalog.create.smartbox.field_offers')">
                            @foreach ($offerOptions as $slug => $label)
                                <flux:checkbox value="{{ $slug }}" :label="$label" wire:key="sb-offer-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="offers" />
                        <flux:error name="offers.*" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model="additional" :label="__('partner.smartbox_offers.additional_heading')">
                            @foreach ($additionalOptions as $slug => $label)
                                <flux:checkbox value="{{ $slug }}" :label="$label" wire:key="sb-add-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="additional" />
                        <flux:error name="additional.*" />
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_included')">
                <div class="flex flex-col gap-2 p-5 {{ $checkboxes }}">
                    <flux:checkbox.group wire:model="included" :label="__('admin-catalog.create.smartbox.field_included')">
                        @foreach ($includedOptions as $slug => $label)
                            <flux:checkbox value="{{ $slug }}" :label="$label" wire:key="sb-inc-{{ $slug }}" />
                        @endforeach
                    </flux:checkbox.group>
                    <flux:error name="included" />
                    <flux:error name="included.*" />
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_animals')">
                <div class="flex flex-col gap-4 p-5 {{ $checkboxes }}">
                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model.live="animalServices" :label="__('admin-catalog.create.smartbox.field_animal_services')">
                            @foreach ($animalOptions as $slug => $label)
                                <flux:checkbox value="{{ $slug }}" :label="$label" wire:key="sb-animal-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="animalServices" />
                        <flux:error name="animalServices.*" />
                    </div>
                    @if (in_array('altro', $animalServices, true))
                        <flux:textarea wire:model="animalOther" rows="2" maxlength="200" :label="__('admin-catalog.create.smartbox.field_animal_other')" />
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_structures')">
                <div class="flex flex-col gap-4 p-5 {{ $checkboxes }}">
                    @if ($structureOptions === [])
                        <p class="m-0 text-[13px] text-gray-600">{{ __('admin-catalog.create.smartbox.structures_empty') }}</p>
                    @else
                        <flux:checkbox.group wire:model="structures" :label="__('admin-catalog.create.smartbox.field_structures')" :description="__('admin-catalog.create.smartbox.structures_help')">
                            @foreach ($structureOptions as $id => $label)
                                <flux:checkbox value="{{ $id }}" :label="$label" wire:key="sb-structure-{{ $id }}" />
                            @endforeach
                        </flux:checkbox.group>
                    @endif
                    {{-- Le regole falliscono su structures.N: senza questo il rifiuto
                         di un id manomesso resterebbe invisibile. --}}
                    <flux:error name="structures" />
                    <flux:error name="structures.*" />
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_photos')">
                <div class="flex flex-col gap-4 p-5">
                    {{-- Lo slot disegna la zona cliccabile: senza, `ui-file-upload`
                         renderebbe il solo input sr-only e non ci sarebbe niente
                         da premere (stesso riquadro di structure-create). --}}
                    <flux:file-upload wire:model="photos" multiple accept="image/*" class="w-full" :label="__('admin-catalog.create.smartbox.section_photos')">
                        <div class="flex min-h-[94px] w-full cursor-pointer flex-col items-center justify-center gap-1 rounded-lg bg-gray-100 px-4 py-4 text-center">
                            <span class="text-[14.5px] font-bold text-admin-teal">{{ __('admin-catalog.create.photos_help') }}</span>
                        </div>
                    </flux:file-upload>
                    <div wire:loading wire:target="photos" class="text-[13px] text-gray-600">{{ __('partner.hotel_photos.uploading') }}</div>
                    <flux:error name="photos" />
                    <flux:error name="photos.*" />
                    @if ($photos !== [])
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ($photos as $index => $photo)
                                <div wire:key="smartbox-photo-{{ $index }}" class="flex flex-col gap-2">
                                    @if (method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                        <img src="{{ $photo->temporaryUrl() }}" alt="" class="h-28 w-full rounded-[6px] object-cover" />
                                    @else
                                        <div class="h-28 w-full rounded-[6px] bg-gray-100"></div>
                                    @endif
                                    @if ($index === 0)
                                        <x-admin.badge tone="info">{{ __('admin-catalog.create.cover') }}</x-admin.badge>
                                    @endif
                                    <x-admin.button tone="ghost" wire:click="removePhoto({{ $index }})">{{ __('partner.hotel_photos.delete') }}</x-admin.button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_cancellation')">
                <div class="p-5">
                    {{-- Elenco da ServiceOptionLabels, come la whitelist di
                         `when`: scritto a mano divergerebbe dalla regola. --}}
                    <flux:select wire:model="when" class="max-w-[260px]" :label="__('admin-catalog.create.smartbox.field_cancellation')">
                        @foreach ($cancellationOptions as $slug => $label)
                            <flux:select.option value="{{ $slug }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.smartbox.section_price')">
                <div class="p-5">
                    <flux:input wire:model="price" inputmode="decimal" class="max-w-[220px]" :label="__('admin-catalog.create.smartbox.field_price')" :description="__('admin-catalog.create.smartbox.price_help')" />
                </div>
            </x-admin.card>
        </div>

        @include('livewire.admin.catalog.partials.partner-aside')
    </div>
</div>
