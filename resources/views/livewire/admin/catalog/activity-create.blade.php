{{-- Pannello — nuova attività o evento per conto di un partner (spec §5.2). --}}
@php
    $checkboxes = '[--color-accent:#6CD1EF] [&_[data-flux-checkbox]]:!rounded-full [&_[data-flux-checkbox]_*]:!rounded-full';
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.catalog.index')">{{ __('admin-catalog.create.activity.back') }}</x-admin.back-link>

    <x-admin.page-header :heading="__('admin-catalog.create.activity.heading')" :sub="__('admin-catalog.create.activity.sub')">
        <x-slot:actions>
            <x-admin.button tone="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save" :disabled="$created">{{ __('admin-catalog.create.submit') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid items-start gap-3.5 lg:grid-cols-3">
        <div class="flex min-w-0 flex-col gap-3.5 lg:col-span-2">

            <x-admin.card :heading="__('admin-catalog.create.activity.section_type')">
                <div class="p-5">
                    <flux:radio.group wire:model.live="type" variant="cards" class="[--color-accent:#6CD1EF]">
                        <flux:radio value="attivita" :label="__('partner.activity_type.attivita')" />
                        <flux:radio value="eventi" :label="__('partner.activity_type.eventi')" />
                    </flux:radio.group>
                    <flux:error name="type" />
                </div>
            </x-admin.card>

            {{-- Una sola pila di schede lingua per tutta la pagina: i campi it/en
                 restano montati e il salvataggio salta sulla lingua che sbaglia. --}}
            <x-admin.card :heading="__('admin-catalog.create.activity.section_texts')">
                <x-admin.tabs class="px-5" model="lang" :current="$lang" :items="[
                    ['key' => 'it', 'label' => __('admin-content.common.lang_it')],
                    ['key' => 'en', 'label' => __('admin-content.common.lang_en')],
                ]" />
                <div class="p-5">
                    @foreach (['it', 'en'] as $locale)
                        <div wire:key="activity-texts-{{ $locale }}" @class(['flex flex-col gap-4', 'hidden' => $lang !== $locale])>
                            <flux:input wire:model="name.{{ $locale }}" maxlength="110" :label="__('admin-catalog.create.activity.field_name')" />
                            <flux:textarea wire:model="description.{{ $locale }}" rows="3" maxlength="200" :label="__('admin-catalog.create.activity.field_description')" />
                            @if ($type === 'attivita')
                                <flux:textarea wire:model="detailedDescription.{{ $locale }}" rows="5" maxlength="200" :label="__('admin-catalog.create.activity.field_detailed_description')" :description="__('admin-catalog.create.activity.detailed_help')" />
                            @endif
                            <flux:input wire:model="location.meetingPoint.{{ $locale }}" maxlength="110" :label="__('admin-catalog.create.activity.field_meeting_point')" />
                            <flux:textarea wire:model="included.additionalOther.{{ $locale }}" rows="2" maxlength="200" :label="__('admin-catalog.create.activity.field_additional_other')" />
                            <flux:textarea wire:model="animalOther.{{ $locale }}" rows="2" maxlength="200" :label="__('admin-catalog.create.activity.field_animal_other')" />
                        </div>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.activity.section_location')">
                <div class="grid items-start gap-4 p-5 sm:grid-cols-2">
                    <flux:input wire:model="location.address" class="sm:col-span-2" :label="__('admin-catalog.create.activity.field_address')" />
                    <flux:input wire:model="location.city" :label="__('admin-catalog.create.activity.field_city')" />
                    <flux:input wire:model="location.zip" inputmode="numeric" :label="__('admin-catalog.create.activity.field_zip')" />
                    {{-- Composto a mano come in structure-create: x-partner.province-select
                         porta lo stile del form partner (etichetta 12px grigia, campo
                         quadrato, asterisco) e nel pannello sarebbe l'unico campo diverso
                         da tutti gli altri. Field a mano = flux:error esplicito. --}}
                    <flux:field>
                        <flux:label>{{ __('admin-catalog.create.activity.field_province') }}</flux:label>
                        <flux:select variant="listbox" searchable wire:model="location.province" :placeholder="__('admin-catalog.create.activity.field_province')">
                            @foreach ($provinces as $province)
                                <flux:select.option value="{{ $province->short_name }}" wire:key="province-{{ $province->short_name }}">
                                    {{ $province->name }} ({{ $province->short_name }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="location.province" />
                    </flux:field>
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.activity.section_info')">
                <div class="grid items-start gap-4 p-5 sm:grid-cols-2">
                    {{-- flux:date-picker composto a mano: lo slot d'errore non è
                         iniettato, senza flux:error il salvataggio sembrerebbe morto. --}}
                    <flux:field>
                        <flux:label>{{ __('admin-catalog.create.activity.field_date_start') }}</flux:label>
                        <flux:date-picker wire:model="info.dateStart" />
                        <flux:error name="info.dateStart" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('admin-catalog.create.activity.field_date_end') }}</flux:label>
                        <flux:date-picker wire:model="info.dateEnd" />
                        <flux:error name="info.dateEnd" />
                    </flux:field>

                    @if ($type === 'eventi')
                        <flux:select wire:model="info.timeStart" :label="__('admin-catalog.create.activity.field_time_start')" placeholder="--:--">
                            @foreach ($times as $time)
                                <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="info.timeEnd" :label="__('admin-catalog.create.activity.field_time_end')" placeholder="--:--">
                            @foreach ($times as $time)
                                <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @else
                        <p class="m-0 text-[13px] text-gray-600 sm:col-span-2">{{ __('admin-catalog.create.activity.times_help') }}</p>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.activity.section_included')">
                <div class="flex flex-col gap-5 p-5 {{ $checkboxes }}">
                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model="included.services" :label="__('admin-catalog.create.activity.field_services')">
                            @foreach ($serviceOptions as $slug => $label)
                                <flux:checkbox value="{{ $slug }}" :label="$label" wire:key="act-svc-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="included.services" />
                        <flux:error name="included.services.*" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model.live="included.additional" :label="__('admin-catalog.create.activity.field_additional')">
                            @foreach ($additionalOptions as $slug => $label)
                                <flux:checkbox value="{{ $slug }}" :label="$label" wire:key="act-add-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="included.additional" />
                        <flux:error name="included.additional.*" />
                    </div>

                    @foreach (['colazione', 'pranzo', 'cena'] as $meal)
                        @if (in_array($meal, $included->additional, true))
                            <div wire:key="activity-meal-{{ $meal }}" class="grid items-start gap-4 sm:grid-cols-2">
                                <flux:select wire:model="included.mealTimes.{{ $meal }}.from" :label="$additionalOptions[$meal].' — '.__('admin-catalog.create.activity.meal_from')" placeholder="--:--">
                                    @foreach ($times as $time)
                                        <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="included.mealTimes.{{ $meal }}.to" :label="$additionalOptions[$meal].' — '.__('admin-catalog.create.activity.meal_to')" placeholder="--:--">
                                    @foreach ($times as $time)
                                        <flux:select.option value="{{ $time }}">{{ $time }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        @endif
                    @endforeach

                    <div class="flex flex-col gap-2">
                        <flux:checkbox.group wire:model="included.structureRules" :label="__('admin-catalog.create.activity.field_rules')">
                            @foreach ($ruleOptions as $slug => $label)
                                <flux:checkbox value="{{ $slug }}" :label="$label" wire:key="act-rule-{{ $slug }}" />
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="included.structureRules" />
                        <flux:error name="included.structureRules.*" />
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.activity.section_animals')">
                <div class="flex flex-col gap-2 p-5 {{ $checkboxes }}">
                    <flux:checkbox.group wire:model.live="animalServices" :label="__('admin-catalog.create.activity.field_animal_services')">
                        @foreach ($animalOptions as $slug => $label)
                            <flux:checkbox value="{{ $slug }}" :label="$label" wire:key="act-animal-{{ $slug }}" />
                        @endforeach
                    </flux:checkbox.group>
                    <flux:error name="animalServices" />
                    <flux:error name="animalServices.*" />
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.activity.section_cost')">
                <div class="flex flex-col gap-4 p-5">
                    <flux:radio.group wire:model.live="costType" :label="__('admin-catalog.create.activity.field_cost_type')" class="[--color-accent:#6CD1EF]">
                        <flux:radio value="pagamento" :label="__('admin-catalog.create.activity.cost_paid')" />
                        <flux:radio value="gratuito" :label="__('admin-catalog.create.activity.cost_free')" />
                    </flux:radio.group>
                    <flux:error name="costType" />
                    @if ($costType === 'pagamento')
                        <flux:input wire:model="pricePerPerson" inputmode="decimal" class="max-w-[220px]" :label="__('admin-catalog.create.activity.field_price_per_person')" :description="__('admin-catalog.create.activity.price_help')" />
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-catalog.create.activity.section_photos')">
                <div class="flex flex-col gap-4 p-5">
                    {{-- Solo foto nuove: la scheda nasce qui, non esiste una bozza
                         già salvata da cui rimuoverle (niente removeSaved).
                         Lo slot disegna la zona cliccabile: senza, `ui-file-upload`
                         renderebbe il solo input sr-only e non ci sarebbe niente
                         da premere (stesso riquadro di structure-create). --}}
                    <flux:file-upload wire:model="photos" multiple accept="image/*" class="w-full" :label="__('admin-catalog.create.activity.section_photos')">
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
                                <div wire:key="activity-photo-{{ $index }}" class="flex flex-col gap-2">
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

            <x-admin.card :heading="__('admin-catalog.create.activity.section_cancellation')">
                <div class="p-5">
                    {{-- L'elenco arriva da ServiceOptionLabels, come la
                         whitelist di `when`: scritto a mano, una quinta finestra
                         aggiunta al Task 1 lascerebbe questo form a offrirne
                         quattro. --}}
                    <flux:select wire:model="when" class="max-w-[260px]" :label="__('admin-catalog.create.activity.field_cancellation')">
                        @foreach ($cancellationOptions as $slug => $label)
                            <flux:select.option value="{{ $slug }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </x-admin.card>
        </div>

        @include('livewire.admin.catalog.partials.partner-aside')
    </div>
</div>
