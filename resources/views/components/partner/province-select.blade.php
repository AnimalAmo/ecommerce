{{-- Select "Provincia" (listbox ricercabile) condivisa dai form partner.
     $provinces dal componente Livewire (Province::orderBy('name')), value = sigla. --}}
@props(['provinces', 'model', 'label'])

<flux:field>
    <flux:label class="!text-xs !font-normal !text-[#555555]">{{ $label }} *</flux:label>
    <flux:select variant="listbox" searchable wire:model="{{ $model }}" :placeholder="$label" class="[&_button]:!h-10 [&_button]:!rounded-[3px] [&_button]:!border-[#C8C8C8] max-lg:[&_button]:!h-[45px] max-lg:[&_button]:!rounded-[5px] max-lg:[&_button]:!text-sm">
        @foreach ($provinces as $province)
            <flux:select.option value="{{ $province->short_name }}">{{ $province->name }} ({{ $province->short_name }})</flux:select.option>
        @endforeach
    </flux:select>
</flux:field>
