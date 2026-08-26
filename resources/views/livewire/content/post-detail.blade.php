{{-- Dettaglio post (XD app "Dettaglio post - scrivi"; la variante "– 1" è questa schermata
     dopo l'invio, con la propria risposta in coda). Il desktop non ha un artboard: riusa
     la card della lista Community dentro il container di pagina. --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';

    // Chip tag sulla card post, per colore (stessa palette della lista Community).
    $chipClasses = [
        '#3E72FF' => 'bg-[#3E72FF] text-white',
        '#555555' => 'bg-[#555555] text-white',
        '#8DE0FF' => 'bg-[#8DE0FF] text-ink',
        '#8DABFF' => 'bg-[#8DABFF] text-white',
        '#C59FFD' => 'bg-[#C59FFD] text-ink',
        '#8E53E6' => 'bg-[#8E53E6] text-white',
        '#FF9F3E' => 'bg-[#FF9F3E] text-ink',
        '#FFE13E' => 'bg-[#FFE13E] text-ink',
    ];
    $chip = $chipClasses[$post['tagColor']] ?? 'bg-[#555555] text-white';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- ===== Desktop: stessa card della lista, con la riga risposta in fondo ===== --}}
        <div class="{{ $px }} pb-[120px] pt-10 max-lg:hidden">
            <div class="mx-auto w-full max-w-[1062px]">
                <a href="{{ route('community') }}" class="inline-flex items-center gap-2 text-sm leading-none text-[#959595]">
                    <flux:icon.arrow-back class="h-4 w-4" />
                    {{ __('profile.back') }}
                </a>

                <article class="mt-6 w-full rounded-[4px] bg-white p-6 shadow-[1px_1px_5px_#0000001A]">
                    <div class="flex items-start justify-between gap-4">
                        <h1 class="text-xl font-bold leading-[27px] text-black">{{ $post['title'] }}</h1>
                        <span class="flex h-[27px] shrink-0 items-center rounded-[3px] px-[10px] text-sm font-medium {{ $chip }}">{{ $post['tag'] }}</span>
                    </div>

                    <p class="mt-[29px] text-[15px] font-bold leading-[21px] text-[#959595]">{{ $post['author'] }}</p>
                    <p class="mt-[13px] whitespace-pre-line text-[15px] leading-[21px] text-ink-700">{{ $post['body'] }}</p>

                    @foreach ($post['replies'] as $reply)
                        <div wire:key="reply-{{ $loop->index }}" class="mt-[22px]">
                            <p class="text-[15px] font-bold leading-[21px] text-[#959595]">{{ $reply['author'] }}</p>
                            <p class="mt-[13px] whitespace-pre-line text-[15px] leading-[21px] text-ink-700">{{ $reply['body'] }}</p>
                        </div>
                    @endforeach

                    {{-- Riga risposta: solo utenti loggati (ospiti leggono soltanto). --}}
                    @auth
                        <div class="relative mt-[26px]">
                            <flux:input type="text" wire:model="draft" wire:keydown.enter="reply" placeholder="{{ __('community.reply_placeholder') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!rounded-full [&_input]:!border [&_input]:!border-[#E9E9E9] [&_input]:!bg-white [&_input]:!pl-6 [&_input]:!pr-[135px] [&_input]:!text-[15px] [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:italic [&_input]:placeholder:text-[#959595]" />
                            <flux:button wire:click="reply" class="!absolute !right-0 !top-0 !h-10 !w-[127px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none">{{ __('community.reply') }}</flux:button>
                        </div>
                    @endauth
                </article>
            </div>
        </div>

        {{-- ===== Mobile (XD 375x812): niente card né tabbar, il post è la pagina e in
                   fondo resta la barra di risposta fissa ===== --}}
        <div class="px-4 pb-[110px] pt-4 lg:hidden">
            @include('partials.profile-mobile-header', ['backHref' => route('community')])

            {{-- XD: 20px fra "Indietro" e il tag; qui la riga indietro è 9px più alta
                 (icona 16 + testo 14 contro i 14 dell'artboard), quindi il margine li recupera --}}
            <span class="mt-[11px] flex h-[26px] w-fit items-center rounded-[3px] px-[10px] text-sm font-medium {{ $chip }}">{{ $post['tag'] }}</span>

            <h1 class="mt-3 text-lg font-bold leading-none text-black">{{ $post['title'] }}</h1>
            <p class="mt-[14px] text-[15px] font-semibold leading-none text-[#C8C8C8]">{{ $post['author'] }}</p>
            <p class="mt-[9px] whitespace-pre-line text-[15px] leading-[21px] text-ink-700">{{ $post['body'] }}</p>

            @foreach ($post['replies'] as $reply)
                <div wire:key="reply-mobile-{{ $loop->index }}" class="mt-[21px]">
                    <p class="text-[15px] font-semibold leading-none text-[#C8C8C8]">{{ $reply['author'] }}</p>
                    <p class="mt-[9px] whitespace-pre-line text-[15px] leading-[21px] text-ink-700">{{ $reply['body'] }}</p>
                </div>
            @endforeach
        </div>
    </main>

    <div class="max-lg:hidden">
        @include('partials.site-footer')
    </div>

    {{-- Barra di risposta fissa (XD: rettangolo bianco h86 con ombra, pill 343x40 r28).
         Prende il posto della tabbar, come nell'artboard. --}}
    <div class="fixed inset-x-0 bottom-0 z-40 h-[86px] bg-white px-4 pt-[14px] shadow-[0px_0px_6px_#0000001C] lg:hidden">
        @auth
            {{-- Il bottone "invia" compare solo quando c'è del testo (XD: stato base senza bottone).
                 Il controllo è client-side su $wire, così non serve un roundtrip per ogni carattere. --}}
            <div class="relative" x-data>
                <flux:input type="text" wire:model="draft" wire:keydown.enter="reply" placeholder="{{ __('community.reply_placeholder') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!rounded-[28px] [&_input]:!border [&_input]:!border-[#E2EAEB] [&_input]:!bg-white [&_input]:!pl-3 [&_input]:!pr-[64px] [&_input]:!text-sm [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:italic [&_input]:placeholder:text-[#959595]" />

                <flux:button wire:click="reply" x-show="$wire.draft.trim().length > 0" x-cloak aria-label="{{ __('community.send') }}" class="!absolute !right-[11px] !top-[7px] !h-[27px] !w-[46px] !rounded-[20px] !border-0 !bg-[#0D171A] !px-0 !text-white !shadow-none hover:!bg-[#0D171A]">
                    <flux:icon.arrow-up-line class="h-6 w-6" />
                </flux:button>
            </div>
        @else
            {{-- L'artboard non ha lo stato ospite: come nella lista, chi non è loggato legge
                 soltanto e trova al suo posto la CTA di accesso. --}}
            <flux:modal.trigger name="login">
                <flux:button class="!h-10 !w-full !rounded-[28px] !border-0 !bg-brand-yellow !text-sm !font-bold !text-ink !shadow-none hover:!bg-[#0D171A] hover:!text-white">{{ __('nav.login_register') }}</flux:button>
            </flux:modal.trigger>
        @endauth
    </div>
</div>
