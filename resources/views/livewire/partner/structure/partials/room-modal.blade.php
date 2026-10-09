{{--
    Modale di una stanza (ManagesRoomRows), condivisa fra lo step 5 del wizard
    e la creazione struttura del pannello. Lavora sulla copia `roomForm`; le
    righe cambiano solo con «Salva stanza» (saveRoom). Variabili:
    $whole (alloggio intero), $roomTypes (slug => label), $roomAmenities
    (ServiceOptionLabels::roomAmenityOptions(): services e animal_services, slug => label),
    $pendingUploads (pannello: foto in upload per chiave stanza).
--}}
@php
    $pendingUploads = $pendingUploads ?? [];
    $roomKey = (string) ($roomForm['key'] ?? '');
@endphp

<flux:modal name="room-modal" wire:model.self="roomModal" class="w-full max-w-[720px]" @close="closeRoom">
    @if ($roomModal && $roomForm !== [])
        <div class="flex flex-col gap-5" wire:key="room-modal-{{ $roomKey }}">
            <flux:heading size="lg">
                {{ __($whole ? 'partner.hotel_rooms.whole_modal' : ($editing === null ? 'partner.hotel_rooms.modal_new' : 'partner.hotel_rooms.modal_edit')) }}
            </flux:heading>

            @unless ($whole)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('partner.hotel_rooms.name') }} · {{ __('partner.hotel_rooms.lang_it') }}</flux:label>
                        <flux:input wire:model="roomForm.name.it" maxlength="80" />
                        <flux:description>{{ __('partner.hotel_rooms.name_help') }}</flux:description>
                        <flux:error name="roomForm.name.it" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('partner.hotel_rooms.name') }} · {{ __('partner.hotel_rooms.lang_en') }}</flux:label>
                        <flux:input wire:model="roomForm.name.en" maxlength="80" />
                        <flux:error name="roomForm.name.en" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('partner.hotel_rooms.room_type') }} *</flux:label>
                    <flux:select wire:model="roomForm.type" :placeholder="__('partner.hotel_rooms.room_type')">
                        @foreach ($roomTypes as $slug => $label)
                            <flux:select.option value="{{ $slug }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="roomForm.type" />
                </flux:field>
            @endunless

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('partner.hotel_rooms.description') }} · {{ __('partner.hotel_rooms.lang_it') }}</flux:label>
                    <flux:textarea wire:model="roomForm.description.it" rows="3" maxlength="1000" />
                    <flux:error name="roomForm.description.it" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('partner.hotel_rooms.description') }} · {{ __('partner.hotel_rooms.lang_en') }}</flux:label>
                    <flux:textarea wire:model="roomForm.description.en" rows="3" maxlength="1000" />
                    <flux:error name="roomForm.description.en" />
                </flux:field>
            </div>

            <div class="grid grid-cols-2 gap-4 {{ $whole ? 'md:grid-cols-3' : 'md:grid-cols-4' }}">
                <flux:field>
                    <flux:label>{{ __($whole ? 'partner.hotel_rooms.whole_price' : 'partner.hotel_rooms.price') }} (€) *</flux:label>
                    <flux:input type="number" min="0.01" step="0.01" wire:model="roomForm.price" />
                    <flux:error name="roomForm.price" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __($whole ? 'partner.hotel_rooms.beds' : 'partner.hotel_rooms.max_guests') }} *</flux:label>
                    <flux:input type="number" min="1" max="{{ $whole ? 50 : 20 }}" wire:model="roomForm.max_guests" />
                    <flux:error name="roomForm.max_guests" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('partner.hotel_rooms.max_animals') }} *</flux:label>
                    <flux:input type="number" min="0" max="10" wire:model="roomForm.max_animals" />
                    <flux:error name="roomForm.max_animals" />
                </flux:field>
                @unless ($whole)
                    <flux:field>
                        <flux:label>{{ __('partner.hotel_rooms.units') }} *</flux:label>
                        <flux:input type="number" min="1" wire:model="roomForm.units" />
                        <flux:error name="roomForm.units" />
                    </flux:field>
                @endunless
            </div>
            @unless ($whole)
                <p class="-mt-3 text-[13px] text-[#627277]">{{ __('partner.hotel_rooms.units_help') }}</p>
            @endunless

            {{-- Foto: stesse regole dello step foto (immagini, 8 MB), al massimo 10 per stanza. --}}
            <flux:field>
                <flux:label>{{ __('partner.hotel_rooms.photos') }}</flux:label>
                <flux:file-upload wire:model="roomPhotos" multiple accept="image/*" class="w-full">
                    <div class="flex min-h-[72px] cursor-pointer flex-col items-center justify-center gap-1 rounded-[2px] bg-[#F4F4F4] px-4 py-3 text-center">
                        <span class="text-[14px] font-bold text-brand-cyan">{{ __('partner.hotel_rooms.photos_drop') }}</span>
                        <span class="text-[12px] text-[#627277]">{{ __('partner.hotel_rooms.photos_hint') }}</span>
                    </div>
                </flux:file-upload>
                <div wire:loading wire:target="roomPhotos" class="text-[13px] text-[#959595]">{{ __('partner.hotel_rooms.uploading') }}</div>
                <flux:error name="roomPhotos" />
                <flux:error name="roomPhotos.*" />
                <flux:error name="roomForm.photos" />

                @php $pendingSaved = $pendingUploads[$roomKey] ?? []; @endphp
                @if (count($roomForm['photos'] ?? []) || count($roomPhotos) || count($pendingSaved))
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($roomForm['photos'] ?? [] as $p => $path)
                            <div class="relative h-[72px] w-[96px] overflow-hidden rounded-[4px] border border-[#E2EAEB] bg-[#F4F4F4]" wire:key="room-saved-{{ $p }}">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="" class="h-full w-full object-cover">
                                <button type="button" wire:click="removeRoomPhoto({{ $p }})" class="absolute top-1 right-1 rounded-full bg-white/90 p-0.5 text-[#F85933]" aria-label="{{ __('partner.hotel_rooms.delete') }}"><flux:icon.x-mark class="h-4 w-4" /></button>
                            </div>
                        @endforeach
                        @foreach ($pendingSaved as $p => $file)
                            <div class="relative h-[72px] w-[96px] overflow-hidden rounded-[4px] border border-[#E2EAEB] bg-[#F4F4F4]" wire:key="room-pending-{{ $p }}">
                                @if (is_object($file) && method_exists($file, 'isPreviewable') && $file->isPreviewable())
                                    <img src="{{ $file->temporaryUrl() }}" alt="" class="h-full w-full object-cover">
                                @endif
                                <button type="button" wire:click="removePendingRoomUpload('{{ $roomKey }}', {{ $p }})" class="absolute top-1 right-1 rounded-full bg-white/90 p-0.5 text-[#F85933]" aria-label="{{ __('partner.hotel_rooms.delete') }}"><flux:icon.x-mark class="h-4 w-4" /></button>
                            </div>
                        @endforeach
                        @foreach ($roomPhotos as $p => $file)
                            <div class="relative h-[72px] w-[96px] overflow-hidden rounded-[4px] border border-[#E2EAEB] bg-[#F4F4F4]" wire:key="room-upload-{{ $p }}">
                                @if (is_object($file) && method_exists($file, 'isPreviewable') && $file->isPreviewable())
                                    <img src="{{ $file->temporaryUrl() }}" alt="" class="h-full w-full object-cover">
                                @endif
                                <button type="button" wire:click="removeRoomUpload({{ $p }})" class="absolute top-1 right-1 rounded-full bg-white/90 p-0.5 text-[#F85933]" aria-label="{{ __('partner.hotel_rooms.delete') }}"><flux:icon.x-mark class="h-4 w-4" /></button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:field>

            {{-- Servizi della camera: stessi cataloghi degli step 7 (hotel) e 8 (animali).
                 Un solo gruppo, un solo array `amenities`: la scheda pubblica li separa
                 nei box «Servizi hotel» e «Servizi animali» della stanza scelta. --}}
            <flux:checkbox.group wire:model="roomForm.amenities" :label="__('partner.hotel_rooms.amenities')">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($roomAmenities['services'] as $slug => $label)
                        <flux:checkbox value="{{ $slug }}" :label="$label" />
                    @endforeach
                </div>
                <p class="mt-4 text-sm font-medium text-zinc-800">{{ __('partner.hotel_rooms.animal_amenities') }}</p>
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($roomAmenities['animal_services'] as $slug => $label)
                        <flux:checkbox value="{{ $slug }}" :label="$label" />
                    @endforeach
                </div>
            </flux:checkbox.group>
            <flux:error name="roomForm.amenities.*" />

            <div class="flex items-center justify-end gap-3">
                <flux:button type="button" variant="ghost" wire:click="closeRoom">{{ __('partner.hotel_rooms.cancel') }}</flux:button>
                <flux:button type="button" variant="primary" wire:click="saveRoom" wire:loading.attr="disabled" wire:target="saveRoom,roomPhotos">{{ __('partner.hotel_rooms.save') }}</flux:button>
            </div>
        </div>
    @endif
</flux:modal>
