{{-- Dashboard B2B - smartbox - aggiungi strutture (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x…, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 10 di 12" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.smartbox_structures.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.smartbox_structures.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.smartbox_structures.section') }}</p>

                <form wire:submit="next" class="mt-6">
                    {{-- Griglia strutture selezionabili (card 226x175 circa, r10) --}}
                    <div class="grid grid-cols-2 gap-5 sm:grid-cols-3">
                        @foreach ($options as $key => $opt)
                            <label wire:key="struct-{{ $key }}"
                                @class([
                                    'group relative flex cursor-pointer flex-col overflow-hidden rounded-[10px] border bg-white transition',
                                    'border-brand-cyan ring-2 ring-brand-cyan' => in_array($key, $structures, true),
                                    'border-[#E2EAEB] hover:border-[#C8C8C8]' => !in_array($key, $structures, true),
                                ])>
                                {{-- Area foto (placeholder) --}}
                                <div class="flex h-[110px] items-center justify-center bg-[#F4F4F4] text-[#C8C8C8]">
                                    <flux:icon.photo class="h-8 w-8" />
                                </div>
                                {{-- Checkbox in overlay --}}
                                <div class="absolute right-2 top-2 [--color-accent:#6CD1EF] [&_[data-flux-checkbox]]:!rounded-full [&_[data-flux-checkbox]_*]:!rounded-full">
                                    <flux:checkbox wire:model.live="structures" value="{{ $key }}" />
                                </div>
                                {{-- Nome + città --}}
                                <div class="px-3 py-2">
                                    <p class="text-[15px] font-semibold text-black">{{ $opt['name'] }}</p>
                                    <p class="mt-1 text-[13px] font-semibold text-[#555555]">{{ $opt['city'] }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @error('structures')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    {{-- Carica altro (link cyan, visivo) --}}
                    <div class="mt-6 text-center">
                        <flux:button type="button" variant="ghost" class="!px-0 !text-[15px] !font-bold !text-brand-cyan hover:!bg-transparent hover:!text-[#4bb8d8]">{{ __('partner.smartbox_structures.load_more') }}</flux:button>
                    </div>

                    {{-- Azioni: Indietro (a cosa è incluso animali) + Avanti (pill scuro) --}}
                    <div class="mt-6 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.smartbox.included-animals') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.smartbox_structures.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.smartbox_structures.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
