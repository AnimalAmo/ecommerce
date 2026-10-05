{{--
    Foto della scheda (CatalogPhotos). La prima è la copertina: è la foto delle
    card e dell'hero, e apre «Vedere tutte le foto». Frecce e non drag&drop:
    funzionano uguali da telefono e con la tastiera.
--}}
<x-admin.card :heading="__('admin-catalog.show.photos.heading')">
    <x-slot:aside>
        <span class="text-[12.5px] text-gray-400">{{ trans_choice('admin-catalog.show.photos.count', count($photos), ['count' => count($photos), 'min' => $min]) }}</span>
    </x-slot:aside>

    <div class="flex flex-col gap-4 p-5">
        @if ($partnerChanges)
            <x-admin.notice tone="warning">{{ __('admin-catalog.show.photos.partner_changes') }}</x-admin.notice>
        @endif

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            @foreach ($photos as $index => $photo)
                <div wire:key="photo-{{ $photo['token'] }}" class="flex flex-col gap-2 rounded-lg border border-admin-row p-2">
                    @if ($photo['url'])
                        <img src="{{ $photo['url'] }}" alt="" class="h-28 w-full rounded-[6px] object-cover" />
                    @else
                        <div class="h-28 w-full rounded-[6px] bg-gray-100"></div>
                    @endif

                    <div class="flex min-h-[22px] flex-wrap gap-1.5">
                        @if ($index === 0)
                            <x-admin.badge tone="info">{{ __('admin-catalog.create.cover') }}</x-admin.badge>
                        @endif
                        @if ($photo['new'])
                            <x-admin.badge tone="warning">{{ __('admin-catalog.show.photos.new') }}</x-admin.badge>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-1">
                        <flux:button size="xs" variant="ghost" icon="chevron-left" wire:click="move({{ $index }}, -1)" :disabled="$index === 0" :aria-label="__('admin-catalog.show.photos.move_before')" />
                        <flux:button size="xs" variant="ghost" icon="chevron-right" wire:click="move({{ $index }}, 1)" :disabled="$index === count($photos) - 1" :aria-label="__('admin-catalog.show.photos.move_after')" />
                        @if ($index !== 0)
                            <flux:button size="xs" variant="ghost" icon="star" wire:click="makeCover({{ $index }})" :aria-label="__('admin-catalog.show.photos.make_cover')" :tooltip="__('admin-catalog.show.photos.make_cover')" />
                        @endif
                        <flux:button size="xs" variant="ghost" icon="trash" class="ml-auto !text-red-600" wire:click="remove({{ $index }})" :aria-label="__('admin-catalog.show.photos.remove')" :tooltip="__('admin-catalog.show.photos.remove')" />
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Lo slot disegna la zona cliccabile (vedi activity-create). --}}
        <flux:file-upload wire:model="uploads" multiple accept="image/*" class="w-full" :label="__('admin-catalog.show.photos.add')">
            <div class="flex min-h-[72px] w-full cursor-pointer flex-col items-center justify-center gap-1 rounded-lg bg-gray-100 px-4 py-3 text-center">
                <span class="text-[14.5px] font-bold text-admin-teal">{{ __('admin-catalog.create.photos_help') }}</span>
            </div>
        </flux:file-upload>
        <div wire:loading wire:target="uploads" class="text-[13px] text-gray-600">{{ __('partner.hotel_photos.uploading') }}</div>
        <flux:error name="uploads.*" />
        <flux:error name="photos" />

        <div class="flex flex-wrap items-center justify-end gap-2.5">
            @if ($dirty)
                <span class="mr-auto text-[12.5px] text-gray-400">{{ __('admin-catalog.show.photos.unsaved') }}</span>
                <x-admin.button tone="outline" wire:click="cancel">{{ __('admin-catalog.show.photos.cancel') }}</x-admin.button>
            @endif
            <x-admin.button tone="primary" wire:click="save" :disabled="! $dirty">{{ __('admin-catalog.show.photos.save') }}</x-admin.button>
        </div>
    </div>
</x-admin.card>
