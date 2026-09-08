{{-- Dashboard B2B – struttura ricettiva - foto (XD, artboard 1920x1080 + variante "caricate") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x372 vuota / 816x534 con foto, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 10 di 11" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.hotel_photos.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.activity_photos.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.hotel_photos.section') }}</p>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.hotel_photos.helper') }}</p>

                <form wire:submit="next" class="mt-6">
                    {{-- Dropzone: componente Flux (gestisce click, drag&drop e il file
                         input nascosto). Prima era un <label> costruito a mano con dentro
                         un flux:input sr-only e un handler Alpine per il drop. --}}
                    <flux:file-upload wire:model="photos" multiple accept="image/*" class="w-full">
                        <div class="flex min-h-[94px] cursor-pointer flex-col items-center justify-center gap-1 rounded-[2px] bg-[#F4F4F4] px-4 py-4 text-center">
                            <span class="text-[15px] font-bold text-brand-cyan">{{ __('partner.hotel_photos.drop') }}</span>
                            <span class="flex items-center gap-1 text-[13px] text-[#627277]">
                                <flux:icon.exclamation-circle class="h-4 w-4" />
                                {{ __('partner.hotel_photos.hint') }}
                            </span>
                        </div>
                    </flux:file-upload>

                    <div wire:loading wire:target="photos" class="mt-2 text-[13px] text-[#959595]">{{ __('partner.hotel_photos.uploading') }}</div>

                    @error('photos.*')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @error('photos')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    {{-- Griglia foto caricate (XD "caricate": card 165x129, r4, bordo #E2EAEB + "Elimina" rosso) --}}
                    @if (count($saved) || count($photos))
                        <div class="mt-6 flex flex-wrap gap-[15px]">
                            {{-- Foto già salvate nella bozza --}}
                            @foreach ($saved as $i => $path)
                                <div class="w-[165px]" wire:key="saved-{{ $i }}">
                                    <div class="h-[110px] w-[165px] overflow-hidden rounded-[4px] border border-[#E2EAEB] bg-[#F4F4F4]">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="" class="h-full w-full object-cover">
                                    </div>
                                    <flux:button type="button" variant="ghost" wire:click="removeSaved({{ $i }})" class="!mt-1 !h-auto !px-0 !text-[13px] !font-medium !text-[#F85933] hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:gap-1">
                                        <flux:icon.x-mark class="h-4 w-4" />
                                        {{ __('partner.hotel_photos.delete') }}
                                    </flux:button>
                                </div>
                            @endforeach
                            {{-- Nuove foto in upload --}}
                            @foreach ($photos as $i => $photo)
                                <div class="w-[165px]" wire:key="photo-{{ $i }}">
                                    <div class="h-[110px] w-[165px] overflow-hidden rounded-[4px] border border-[#E2EAEB] bg-[#F4F4F4]">
                                        @if (is_object($photo) && method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                            <img src="{{ $photo->temporaryUrl() }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <flux:button type="button" variant="ghost" wire:click="removePhoto({{ $i }})" class="!mt-1 !h-auto !px-0 !text-[13px] !font-medium !text-[#F85933] hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:gap-1">
                                        <flux:icon.x-mark class="h-4 w-4" />
                                        {{ __('partner.hotel_photos.delete') }}
                                    </flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Azioni: Indietro (a smartbox) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.activity.cost') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.hotel_photos.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.hotel_photos.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
