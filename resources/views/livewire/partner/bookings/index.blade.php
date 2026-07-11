{{-- Prenotazioni partner (XD "Prenotazioni strutture/eventi/attività/smartbox") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <flux:card class="w-full !rounded-[10px] !border !border-gray-150 !bg-white !px-8 !py-[52px]">
                <h1 class="text-[25px] font-bold leading-[30px] text-[#0D171A]">{{ __('partner.bookings.heading') }}</h1>

                <flux:tab.group>
                    {{-- Tab famiglie (attiva: cyan + underline, accent Flux ricolorato sul cyan XD) --}}
                    <flux:tabs wire:model.live="tab" class="mt-[18px] !gap-10 !border-b-0 [--color-accent:#68CDEB] [--color-accent-content:#68CDEB]">
                        @foreach (\App\Livewire\Partner\Bookings\PartnerBookings::TABS as $family)
                            <flux:tab name="{{ $family }}" class="!h-auto !px-0 !pt-0 !pb-[5px] !text-[15px] !leading-none !font-semibold [&_*]:!leading-none {{ $tab === $family ? '' : '!text-[#555555] hover:!text-ink' }}">
                                {{ __('partner.bookings.tab_'.$family) }}
                            </flux:tab>
                        @endforeach
                    </flux:tabs>

                    {{-- Ricerca + filtro data (condivisi tra le tab) --}}
                    <div class="mt-[21px] flex flex-wrap items-center gap-4">
                        <flux:input wire:model.live.debounce.300ms="search" icon="search" placeholder="{{ __('partner.bookings.search_placeholder') }}" class="w-full max-w-[472px] [&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8] [&_svg]:!size-4 [&_svg]:!text-[#555555]" />
                        <flux:date-picker wire:model.live="date" clearable placeholder="{{ __('partner.bookings.date_placeholder') }}" class="w-[300px] [&_button]:!h-10 [&_button]:!rounded-[3px] [&_button]:!border-[#C8C8C8]" />
                    </div>

                    {{-- Un panel per famiglia, ognuno con la sua tabella --}}
                    @foreach ($panels as $family => $panel)
                        <flux:tab.panel name="{{ $family }}" class="!p-0">
                            <flux:table class="mt-7 [&_th]:!px-0 [&_th]:!pr-4 [&_th]:!text-[15px] [&_th]:!font-semibold [&_th]:!text-[#959595] [&_td]:!px-0 [&_td]:!pr-4 [&_td]:!py-[19px] [&_td]:!text-[15px] [&_td]:!text-[#0D171A] [&_thead]:!border-b [&_thead]:!border-[#E2EAEB] [&_tbody_tr]:!border-b [&_tbody_tr]:!border-[#E2EAEB]">
                                <flux:table.columns>
                                    @foreach ($panel['columns'] as $key => $label)
                                        {{-- Label Data/Validità centrata; Prezzo e N. Persone centrate anche nei valori --}}
                                        <flux:table.column :align="in_array($key, ['date', 'price', 'people'], true) ? 'center' : 'start'">{{ __('partner.bookings.'.$label) }}</flux:table.column>
                                    @endforeach
                                    <flux:table.column><span class="sr-only">{{ __('partner.bookings.actions') }}</span></flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @forelse ($panel['rows'] as $row)
                                        <flux:table.row wire:key="booking-{{ $family }}-{{ $row['key'] }}">
                                            @foreach ($panel['columns'] as $key => $label)
                                                <flux:table.cell :align="in_array($key, ['price', 'people'], true) ? 'center' : 'start'">{{ $row[$key] }}</flux:table.cell>
                                            @endforeach
                                            <flux:table.cell>
                                                <div class="flex items-center justify-end gap-3">
                                                    <flux:button variant="ghost" size="sm" square href="{{ route('partner.bookings.show', $row['key']) }}" aria-label="{{ __('partner.bookings.view') }}" class="!h-[26px] !w-[26px] !min-w-0 !rounded-full !border-0 !bg-[#FFF8E5] !text-[#FFCB3E] !shadow-none hover:!bg-[#FFCB3E] hover:!text-white [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                                        <flux:icon.eye class="!h-[14px] !w-[14px]" />
                                                    </flux:button>
                                                    {{-- TODO: stampa prenotazione (nessuna interazione definita nell'XD) --}}
                                                    <flux:button variant="ghost" size="sm" square href="#" aria-label="{{ __('partner.bookings.print') }}" class="!h-[26px] !w-[26px] !min-w-0 !rounded-full !border-0 !bg-[#EFE5FF] !text-[#904EF2] !shadow-none hover:!bg-[#904EF2] hover:!text-white [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                                        <flux:icon.printer class="!h-[14px] !w-[14px]" />
                                                    </flux:button>
                                                </div>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @empty
                                        <flux:table.row>
                                            <flux:table.cell colspan="{{ count($panel['columns']) + 1 }}" class="!py-10 !text-center !text-[#959595]">{{ __('partner.bookings.empty') }}</flux:table.cell>
                                        </flux:table.row>
                                    @endforelse
                                </flux:table.rows>
                            </flux:table>
                        </flux:tab.panel>
                    @endforeach
                </flux:tab.group>
            </flux:card>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
