{{-- Profilo partner – I miei servizi (XD "I miei servizi") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <flux:card class="mx-auto w-full max-w-[910px] !rounded-[10px] !border !border-gray-150 !bg-white !px-6 !py-[30px]">
                <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.services.heading') }}</h1>

                <div class="mt-8 flex flex-col gap-5">
                    @forelse ($services as $service)
                        @php
                            $cover = $service->coverPhotoUrl();
                            $typeTag = ['struttura' => 'Holiday', 'attivita' => 'Eventi', 'smartbox' => 'Smartbox'][$service->family()] ?? '';
                        @endphp
                        <div class="flex flex-col gap-4 rounded-[10px] border border-gray-150 bg-white px-4 py-[14px] sm:flex-row">
                            {{-- Copertina + tag tipologia --}}
                            <div class="relative h-[130px] w-full shrink-0 overflow-hidden bg-gray-100 sm:w-[200px]">
                                @if ($cover)
                                    <img src="{{ $cover }}" alt="{{ $service->name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-[linear-gradient(296deg,#FF3EA51A_0%,#68CDEB1A_100%)] text-brand-cyan">
                                        <flux:icon.animal class="h-10 w-10" />
                                    </div>
                                @endif
                                <flux:badge class="absolute left-2 top-2 !rounded-[3px] !bg-brand-magenta !text-white">{{ $typeTag }}</flux:badge>
                            </div>

                            {{-- Info + azioni --}}
                            <div class="flex flex-1 flex-col">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        @if ($service->date_start)
                                            <p class="mb-1 flex items-center gap-1.5 text-[13px] font-semibold text-[#FF3EA5]">
                                                <flux:icon.clock class="h-4 w-4" />
                                                {{ $service->date_start->isoFormat('ddd, D MMM') }}@if ($service->time_start) · {{ $service->time_start }}@endif
                                            </p>
                                        @endif
                                        <h3 class="text-lg font-bold text-[#0D171A]">{{ $service->name }}</h3>
                                        <p class="mt-1 flex items-center gap-1 text-sm font-medium text-[#959595]">
                                            <flux:icon.pin class="h-4 w-4 shrink-0" />
                                            {{ $service->locationLabel() }}
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <flux:button variant="ghost" size="sm" square href="#" aria-label="{{ __('partner.services.edit') }}" class="!rounded-full !bg-[#FFF2E6] !text-[#FF9F3E] hover:!bg-[#FF9F3E] hover:!text-white">
                                            <flux:icon.pencil class="h-5 w-5" />
                                        </flux:button>
                                        <flux:button variant="ghost" size="sm" square wire:click="$dispatch('delete-service', { id: {{ $service->id }} })" aria-label="{{ __('partner.services.delete') }}" class="!rounded-full !bg-[#FDEBE8] !text-[#F85933] hover:!bg-[#F85933] hover:!text-white">
                                            <flux:icon.trash class="h-5 w-5" />
                                        </flux:button>
                                    </div>
                                </div>

                                <div class="mt-auto flex justify-end pt-4">
                                    <flux:link href="{{ route('partner.services.show', $service) }}" class="!inline-flex !items-center !gap-1 !text-[13px] !font-semibold !text-[#555555] !no-underline hover:!text-ink">
                                        {{ __('partner.services.view_details') }}
                                        <flux:icon.chevron-right class="h-4 w-4" />
                                    </flux:link>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[10px] border border-dashed border-gray-150 bg-white p-10 text-center text-[15px] font-medium text-[#959595]">
                            {{ __('partner.services.empty') }}
                        </div>
                    @endforelse
                </div>
            </flux:card>
        </div>
    </main>

    @include('partials.partner-footer')

    {{-- Popup elimina servizio: componente Livewire ad-hoc --}}
    <livewire:partner.my-services.delete-service-modal />
</div>
