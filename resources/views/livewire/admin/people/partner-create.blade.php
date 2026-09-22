{{--
    Nuovo partner dal pannello. Campi con `label` come prop: Flux li avvolge
    in un field completo, errore compreso (con un field composto a mano il
    submit rifiutato sarebbe muto, per questo il telefono ha il suo
    flux:error esplicito). Griglie con items-start: un errore non deve
    allungare la riga.
--}}
@php
    $f = fn (string $field): string => __('admin-people.partner_create.fields.'.$field);
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.users.index')">{{ __('admin-people.users.back') }}</x-admin.back-link>

    <x-admin.page-header :heading="__('admin-people.partner_create.title')" :sub="__('admin-people.partner_create.sub')" />

    <form wire:submit="save" class="flex flex-col gap-3.5">
        <div class="grid items-start gap-3.5 lg:grid-cols-2">
            <x-admin.card :heading="__('admin-people.partner_create.sections.person')">
                <div class="grid items-start gap-4 p-5 sm:grid-cols-2">
                    <flux:input wire:model="form.firstName" :label="$f('firstName')" />
                    <flux:input wire:model="form.lastName" :label="$f('lastName')" />
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.email" type="email" :label="$f('email')" />
                        @if ($existingUserId !== null)
                            <a href="{{ route('admin.users.show', $existingUserId) }}" wire:navigate class="mt-2 inline-block text-[13.5px] font-bold text-admin-teal hover:underline">{{ __('admin-people.inbox.open_account') }}</a>
                        @endif
                    </div>
                    {{-- Stesso input con prefisso dei moduli del sito: la property riceve sempre l'E.164. --}}
                    <flux:field class="sm:col-span-2">
                        <flux:label>{{ $f('phone') }}</flux:label>
                        <x-phone-input model="form.phone" :value="$form->phone" />
                        <flux:error name="form.phone" />
                    </flux:field>
                </div>
            </x-admin.card>

            <x-admin.card :heading="__('admin-people.partner_create.sections.business')">
                <div class="grid items-start gap-4 p-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.businessName" :label="$f('businessName')" />
                    </div>
                    <flux:input wire:model="form.vat" :label="$f('vat')" />
                    <flux:input wire:model="form.taxCode" :label="$f('taxCode')" />
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.address" :label="$f('address')" />
                    </div>
                    <flux:select variant="listbox" searchable wire:model="form.province" :label="$f('province')" :placeholder="__('admin-people.partner_create.province_placeholder')">
                        @foreach ($provinces as $province)
                            <flux:select.option value="{{ $province->short_name }}" wire:key="province-{{ $province->id }}">{{ $province->name }} ({{ $province->short_name }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="form.zip" inputmode="numeric" :label="$f('zip')" />
                </div>
            </x-admin.card>
        </div>

        <x-admin.card :heading="__('admin-people.partner_create.sections.payment')">
            <div class="flex flex-col gap-4 p-5">
                <flux:radio.group wire:model="form.paymentMode" :label="$f('paymentMode')">
                    <flux:radio value="online" :label="__('admin-people.partner_create.payment.online')" />
                    <flux:radio value="on_site" :label="__('admin-people.partner_create.payment.on_site')" />
                </flux:radio.group>
                <flux:input wire:model="form.paymentUrl" type="url" :label="$f('paymentUrl')" :description="__('admin-people.partner_create.payment.url_help')" />
            </div>
        </x-admin.card>

        <div class="flex justify-end">
            <x-admin.button tone="primary" type="submit" icon="plus">{{ __('admin-people.partner_create.submit') }}</x-admin.button>
        </div>
    </form>
</div>
