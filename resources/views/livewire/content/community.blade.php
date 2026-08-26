{{-- Community — tab Tutti i post / I miei post (XD: "Community" / "Community – filtro" / "Community – I miei post") --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';

    // Colori "selected" delle pill tag del composer (chiaro = testo ink, scuro = testo bianco).
    $tagPill = [
        'Avventura' => 'data-checked:!bg-[#3E72FF] data-checked:!text-white hover:data-checked:!bg-[#3E72FF] hover:data-checked:!text-white',
        'Soggiorno' => 'data-checked:!bg-[#8DE0FF] data-checked:!text-ink hover:data-checked:!bg-[#8DE0FF] hover:data-checked:!text-ink',
        'Benessere' => 'data-checked:!bg-[#8DABFF] data-checked:!text-ink hover:data-checked:!bg-[#8DABFF] hover:data-checked:!text-ink',
        'Eventi' => 'data-checked:!bg-[#C59FFD] data-checked:!text-ink hover:data-checked:!bg-[#C59FFD] hover:data-checked:!text-ink',
        'Attività' => 'data-checked:!bg-[#8E53E6] data-checked:!text-white hover:data-checked:!bg-[#8E53E6] hover:data-checked:!text-white',
        'Strutture' => 'data-checked:!bg-[#FF9F3E] data-checked:!text-ink hover:data-checked:!bg-[#FF9F3E] hover:data-checked:!text-ink',
        'Servizi' => 'data-checked:!bg-[#FFE13E] data-checked:!text-ink hover:data-checked:!bg-[#FFE13E] hover:data-checked:!text-ink',
    ];

    // Chip tag sulla card post, per colore (stessa palette delle pill).
    $chipClasses = [
        '#3E72FF' => 'bg-[#3E72FF] text-white',
        '#555555' => 'bg-[#555555] text-white',
        '#8DE0FF' => 'bg-[#8DE0FF] text-ink',
        '#8DABFF' => 'bg-[#8DABFF] text-white', // XD "I miei post": chip Benessere con testo bianco
        '#C59FFD' => 'bg-[#C59FFD] text-ink',
        '#8E53E6' => 'bg-[#8E53E6] text-white',
        '#FF9F3E' => 'bg-[#FF9F3E] text-ink',
        '#FFE13E' => 'bg-[#FFE13E] text-ink',
    ];

    // Chip filtro attivo (artboard "Community – filtro"): coppie bg/testo+icona per tag.
    // Da XD arrivano solo Avventura e Benessere; le altre cinque seguono la stessa ricetta
    // (tinta molto chiara del colore tag come bg, colore tag come testo).
    // Servizi: testo scurito a #D4B500 (il giallo #FFE13E sarebbe illeggibile su fondo chiaro).
    $filterChip = [
        'Avventura' => '!bg-[#E1E9FF] !text-[#3E72FF]',
        'Benessere' => '!bg-[#E8EEFF] !text-[#8DABFF]',
        'Soggiorno' => '!bg-[#E9F9FF] !text-[#8DE0FF]',
        'Eventi' => '!bg-[#F5EDFF] !text-[#C59FFD]',
        'Attività' => '!bg-[#EFE6FC] !text-[#8E53E6]',
        'Strutture' => '!bg-[#FFF2E4] !text-[#FF9F3E]',
        'Servizi' => '!bg-[#FFFADF] !text-[#D4B500]',
    ];
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero foto full-bleed con card composer in vetro smerigliato sopra la foto.
               Solo desktop: l'artboard app apre sul titolo "Community" e sul banner giallo. --}}
        <section class="relative h-[524px] w-full overflow-hidden max-lg:hidden">
            <img src="{{ asset('img/xd/community-hero.jpg') }}" alt="{{ __('community.hero_alt') }}" class="absolute inset-0 h-full w-full object-cover">

            <div class="{{ $px }} relative h-full">
                <div class="mx-auto w-full max-w-[1062px] pt-[102px]">
                    <h1 class="text-4xl font-bold leading-none text-white">{{ __('community.hero_title') }}</h1>
                    <p class="mt-3 text-[18px] text-white/85">{{ __('community.subtitle') }}</p>

                    {{-- Solo gli utenti loggati possono scrivere; gli ospiti leggono e vedono la CTA di accesso. --}}
                    @guest
                        <div class="mt-6 flex w-full flex-wrap items-center justify-between gap-4 rounded-[4px] border border-[#E9E9E9] bg-white/0 px-6 py-5 backdrop-blur-[7px] backdrop-brightness-110">
                            <p class="text-[15px] leading-6 text-white">{{ __('community.login_to_post') }}</p>
                            <flux:modal.trigger name="login">
                                <flux:button class="!shrink-0 !rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white">{{ __('nav.login_register') }}</flux:button>
                            </flux:modal.trigger>
                        </div>
                    @endguest
                    {{-- Card composer (XD "Rettangolo 635": blur 7, brightness +10, fill opacity 0); padding 16/24, altezza fluida così il bottone resta dentro i padding --}}
                    @auth
                    <div class="mt-6 w-full rounded-[4px] border border-[#E9E9E9] bg-white/0 px-6 py-4 backdrop-blur-[7px] backdrop-brightness-110">
                        <h2 class="text-[22px] font-bold leading-[30px] text-white">{{ __('community.composer_heading') }}</h2>

                        <flux:textarea wire:model="composerBody" rows="2" placeholder="{{ __('community.composer_placeholder') }}" class="!mt-[13px] !h-[61px] !min-h-0 !resize-none !rounded-[4px] !border !border-[#E9E9E9] !bg-transparent !text-[15px] !text-white !shadow-none !backdrop-blur-[30px] placeholder:!italic placeholder:!text-white" />

                        <p class="mt-4 text-[15px] leading-5 text-white">{{ __('community.select_tags') }}</p>
                        {{-- Multi-selezione tag: flux checkbox group variant pills; selected = colore per tag ($tagPill) --}}
                        <flux:checkbox.group wire:model="composerTags" variant="pills" class="mt-[10px] flex flex-wrap items-center gap-2">
                            @foreach ($tags as $tag)
                                <flux:checkbox wire:key="composer-tag-{{ $tag }}" value="{{ $tag }}" label="{{ $tag }}" class="!h-[27px] !rounded-[3px] !bg-[#E9E9E9] !px-[10px] !text-sm !font-medium !text-[#959595] hover:!bg-[#E9E9E9] hover:!text-[#959595] {{ $tagPill[$tag] ?? '' }}" />
                            @endforeach
                        </flux:checkbox.group>

                        <div class="mt-3 flex justify-end">
                            <flux:button wire:click="publish" class="!h-[39px] !w-[120px] !rounded-full !border !border-[#E9FF7D] !bg-brand-yellow !text-sm !font-bold !text-[#0D171A] !shadow-none">{{ __('community.publish') }}</flux:button>
                        </div>
                    </div>
                    @endauth
                </div>
            </div>
        </section>

        <div class="{{ $px }} pb-[120px] pt-10 max-lg:hidden">
            <div class="mx-auto w-full max-w-[1062px]">
                {{-- 2. Tab Tutti i post / I miei post (switch Livewire, deep-link ?tab=miei) --}}
                <nav class="flex items-end gap-[41px] border-b border-[#DEDEDE]" aria-label="{{ __('community.sections_aria') }}">
                    <flux:button variant="ghost" wire:click="switchTab('tutti')" :aria-current="$tab === 'tutti' ? 'page' : null" class="relative !h-auto !rounded-none !p-0 !pb-[11px] !text-lg !font-medium hover:!bg-transparent {{ $tab === 'tutti' ? '!text-ink-700 hover:!text-ink-700' : '!text-[#959595]' }}">
                        {{ __('community.tab_all') }}
                        @if ($tab === 'tutti')
                            <span class="absolute inset-x-0 bottom-0 h-[2.5px] translate-y-[1.25px] bg-ink-700" aria-hidden="true"></span>
                        @endif
                    </flux:button>
                    <flux:button variant="ghost" wire:click="switchTab('miei')" :aria-current="$tab === 'miei' ? 'page' : null" class="relative !h-auto !rounded-none !p-0 !pb-[11px] !text-lg !font-medium hover:!bg-transparent {{ $tab === 'miei' ? '!text-ink-700 hover:!text-ink-700' : '!text-[#959595]' }}">
                        {{ __('community.tab_mine') }}
                        @if ($tab === 'miei')
                            <span class="absolute inset-x-0 bottom-0 h-[2.5px] translate-y-[1.25px] bg-ink-700" aria-hidden="true"></span>
                        @endif
                    </flux:button>
                </nav>

                {{-- 3. Toolbar: ricerca + "Filtra tipologia" adiacente (direttiva utente: vicino all'input, non a filo colonna) --}}
                <div class="mt-8 flex items-center gap-4">
                    <div class="flex h-[50px] w-full max-w-[589px] items-center rounded-full border border-[#E9E9E9] bg-white pl-[18px]">
                        <flux:icon.magnifying-glass class="h-[18px] w-[18px] shrink-0 text-[#959595]" />
                        <flux:input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('community.search_placeholder') }}" class="!min-w-0 !flex-1 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-[48px] [&_input]:!rounded-full [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!px-4 [&_input]:!text-lg [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:focus:!outline-none [&_input]:focus-visible:!outline-none [&_input]:placeholder:italic [&_input]:placeholder:text-[#959595]" />
                    </div>

                    {{-- Aperto (XD symbol "Open"): pannello 159px a filo sotto la pill (offset/gap 0), angoli alti squadrati --}}
                    <flux:dropdown align="start" offset="0" gap="0">
                        <flux:button class="!h-[50px] !w-[159px] !shrink-0 !gap-1.5 !rounded-full !border !border-[#E9E9E9] !bg-white !px-2.5 !text-sm !font-normal !text-[#555555] !shadow-none">
                            <span class="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full bg-[#EBF9FD]">
                                <flux:icon.filter class="h-3 w-3" />
                            </span>
                            {{ __('community.filter_type') }}
                            <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-[#555555]" />
                        </flux:button>
                        {{-- Lista piatta: 7 voci da 31px + py-4 ≈ 251px totali come da XD; niente icone né pill hover --}}
                        <flux:menu class="!w-[159px] !min-w-0 !rounded-t-none !rounded-b-[10px] !border-[#E9E9E9] !bg-white !px-0 !py-4 !shadow-none">
                            @foreach ($tags as $tag)
                                <flux:menu.item wire:key="menu-tag-{{ $tag }}" wire:click="addFilter('{{ $tag }}')" class="!h-[31px] !rounded-none !px-4 !py-0 !text-sm !font-normal !text-[#555555] data-active:!bg-gray-50">{{ $tag }}</flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                </div>

                {{-- 4. Chip filtri attivi (artboard "Community – filtro"; riga assente senza filtri) --}}
                @if ($activeFilters !== [])
                    <div class="mt-3 flex h-[27px] flex-wrap items-center gap-2">
                        @foreach ($activeFilters as $filter)
                            {{-- [&>span]: con wire:click Flux avvolge lo slot in uno span display:block (swap spinner) che impilerebbe icona e testo --}}
                            {{-- Geometria XD: × da 10px a sinistra, label a ~22px dal bordo (pl-2 + icona + gap-1), ~12px a destra --}}
                            <flux:button wire:key="filter-{{ $filter }}" wire:click="removeFilter('{{ $filter }}')" aria-label="{{ __('community.remove_filter_aria', ['filter' => $filter]) }}" class="!h-[27px] !rounded-full !border-0 !pl-2 !pr-3 !text-sm !font-medium !shadow-none [&>span]:flex [&>span]:items-center [&>span]:gap-1 {{ $filterChip[$filter] ?? '!bg-[#E9E9E9] !text-[#555555]' }}">
                                <flux:icon.close class="h-[10px] w-[10px] shrink-0" />
                                {{ $filter }}
                            </flux:button>
                        @endforeach
                    </div>
                @endif

                {{-- 5. Card post --}}
                <div class="mt-6 space-y-8">
                    @forelse ($visiblePosts as $post)
                        <article wire:key="post-{{ $post['id'] }}" class="min-h-[315px] w-full rounded-[4px] bg-white p-6 shadow-[1px_1px_5px_#0000001A]">
                            <div class="flex items-start justify-between gap-4">
                                <h3 class="text-xl font-bold leading-[27px] text-black">{{ $post['title'] }}</h3>
                                <span class="flex h-[27px] shrink-0 items-center rounded-[3px] px-[10px] text-sm font-medium {{ $chipClasses[$post['tagColor']] ?? 'bg-[#555555] text-white' }}">{{ $post['tag'] }}</span>
                            </div>

                            <p class="mt-[29px] text-[15px] font-bold leading-[21px] text-[#959595]">{{ $post['author'] }}</p>
                            <p class="mt-[13px] whitespace-pre-line text-[15px] leading-[21px] text-ink-700">{{ $post['body'] }}</p>

                            @foreach ($post['replies'] as $reply)
                                <div wire:key="post-{{ $post['id'] }}-reply-{{ $loop->index }}" class="mt-[22px]">
                                    <p class="text-[15px] font-bold leading-[21px] text-[#959595]">{{ $reply['author'] }}</p>
                                    <p class="mt-[13px] text-[15px] leading-[21px] text-ink-700">{{ $reply['body'] }}</p>
                                </div>
                            @endforeach

                            {{-- Riga risposta: solo utenti loggati (ospiti leggono soltanto). --}}
                            @auth
                            <div class="relative mt-[26px]">
                                <flux:input type="text" wire:model="replyDrafts.{{ $post['id'] }}" wire:keydown.enter="reply({{ $post['id'] }})" placeholder="{{ __('community.composer_placeholder') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!rounded-full [&_input]:!border [&_input]:!border-[#E9E9E9] [&_input]:!bg-white [&_input]:!pl-6 [&_input]:!pr-[135px] [&_input]:!text-[15px] [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:italic [&_input]:placeholder:text-[#959595]" />
                                <flux:button wire:click="reply({{ $post['id'] }})" class="!absolute !right-0 !top-0 !h-10 !w-[127px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none">{{ __('community.reply') }}</flux:button>
                            </div>
                            @endauth
                        </article>
                    @empty
                        {{-- Ricerca/filtro senza risultati (nessun design XD) --}}
                        <p class="text-[15px] leading-[21px] text-[#959595]">{{ __('community.no_posts') }}</p>
                    @endforelse
                </div>

                {{-- 6. Vedi tutto — TODO: paginazione reale --}}
                @if ($visiblePosts !== [])
                    <div class="mt-8 flex justify-center">
                        <flux:button class="!h-10 !w-[135px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none">{{ __('community.see_all') }}</flux:button>
                    </div>
                @endif
            </div>
        </div>

        {{-- ===== Mobile (XD app "Community" 375x1101) =====
             Niente hero né risposte inline: titolo, banner giallo, tab a pillola, ricerca + filtri,
             card post (tag/titolo/autore/testo) e "Vedi altri". Le risposte stanno nel
             dettaglio post, che la card apre. --}}
        <div class="px-4 pb-[120px] pt-4 lg:hidden">
            <h1 class="text-lg font-bold leading-none text-[#0D171A]">{{ __('community.title_mobile') }}</h1>
            <p class="mt-2 text-[15px] text-[#555555]">{{ __('community.subtitle') }}</p>

            {{-- Banner 343x93 #FFF5DD: invito + tondo giallo 40px che apre il composer.
                 Come nella hero desktop, scrivere è riservato ai loggati: l'ospite trova
                 lo stesso banner con la CTA di accesso. --}}
            @auth
                <div class="mt-4 flex h-[93px] flex-col items-center rounded-[4px] bg-[#FFF5DD] px-3 pt-3">
                    <p class="text-center text-[15px] leading-none text-black">{{ __('community.composer_heading') }}</p>

                    <flux:modal.trigger name="scrivi-domanda">
                        <flux:button variant="ghost" square aria-label="{{ __('community.open_composer') }}" class="!mt-[13px] !h-10 !w-10 !rounded-full !bg-[#FDC220] !text-white !shadow-none hover:!bg-[#FDC220] hover:!text-white">
                            <flux:icon.plus class="h-[15px] w-[15px]" stroke-width="2.5" />
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            @else
                <div class="mt-4 flex min-h-[93px] flex-col items-center justify-center gap-[13px] rounded-[4px] bg-[#FFF5DD] px-3 py-3">
                    <p class="text-center text-[15px] leading-5 text-black">{{ __('community.login_to_post') }}</p>

                    <flux:modal.trigger name="login">
                        <flux:button class="!h-[31px] !rounded-full !bg-brand-yellow !px-5 !text-sm !font-bold !text-ink !shadow-none hover:!bg-[#0D171A] hover:!text-white">{{ __('nav.login_register') }}</flux:button>
                    </flux:modal.trigger>
                </div>
            @endauth

            {{-- Pillole condivise con Ordini/Eventi: qui i valori sono le tab di Community --}}
            <div class="mt-6">
                @include('partials.profile-tabs', ['tabs' => ['tutti' => __('community.tab_all'), 'miei' => __('community.tab_mine')]])
            </div>

            <div class="mt-6 flex items-center gap-4">
                <div class="flex h-10 min-w-0 flex-1 items-center gap-[7px] rounded-[28px] border border-[#E2EAEB] bg-white pl-3">
                    <flux:icon.search class="h-[17px] w-[17px] shrink-0 text-[#959595]" />
                    <flux:input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('community.search_placeholder') }}" class="!min-w-0 !flex-1 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-[38px] [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!px-0 [&_input]:!pr-3 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:italic [&_input]:placeholder:text-[#959595]" />
                </div>

                {{-- Tondo grigio 40px (XD symbol "Filtri"): apre il pannello a tutta pagina --}}
                <flux:modal.trigger name="filtri-community">
                    <flux:button variant="ghost" square aria-label="{{ __('community.open_filters') }}" class="!h-10 !w-10 !shrink-0 !rounded-full !bg-[#F2F2F2] !text-[#0D171A] !shadow-none hover:!bg-[#F2F2F2]">
                        <flux:icon.filter-sliders class="h-5 w-5" />
                    </flux:button>
                </flux:modal.trigger>
            </div>

            {{-- Chip filtri attivi (artboard "Community – risultati"): su mobile l'XD le tinge
                 tutte di ciano, non con la tinta per tag del desktop --}}
            @if ($activeFilters !== [])
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @foreach ($activeFilters as $filter)
                        <flux:button wire:key="filter-mobile-{{ $filter }}" wire:click="removeFilter('{{ $filter }}')" aria-label="{{ __('community.remove_filter_aria', ['filter' => $filter]) }}" class="!h-[31px] !rounded-full !border-0 !bg-[#EBF9FD] !pl-4 !pr-3 !text-sm !font-normal !text-[#4FB8D8] !shadow-none [&>span]:flex [&>span]:items-center [&>span]:gap-2">
                            {{ $filter }}
                            <flux:icon.close class="h-[10px] w-[10px] shrink-0" />
                        </flux:button>
                    @endforeach
                </div>
            @endif

            <div class="mt-6 space-y-4">
                @forelse ($visiblePosts as $post)
                    {{-- La card apre il dettaglio (XD "Dettaglio post - scrivi"), dove si risponde --}}
                    <a wire:key="post-mobile-{{ $post['id'] }}" href="{{ route('community.post', $post['id']) }}" aria-label="{{ __('community.open_post', ['title' => $post['title']]) }}" class="block">
                        {{-- Padding XD asimmetrico: 13 sopra, 19 sotto (card 343x273 col testo campione) --}}
                        <article class="w-full rounded-[4px] bg-white px-3 pb-[19px] pt-[13px] shadow-[1px_1px_3px_#0000001A]">
                            <span class="flex h-[26px] w-fit items-center rounded-[3px] px-[10px] text-sm font-medium {{ $chipClasses[$post['tagColor']] ?? 'bg-[#555555] text-white' }}">{{ $post['tag'] }}</span>

                            <h2 class="mt-3 text-lg font-bold leading-none text-black">{{ $post['title'] }}</h2>
                            <p class="mt-[14px] text-[15px] font-semibold leading-none text-[#C8C8C8]">{{ $post['author'] }}</p>
                            <p class="mt-[9px] whitespace-pre-line text-[15px] leading-[21px] text-ink-700">{{ $post['body'] }}</p>
                        </article>
                    </a>
                @empty
                    <p class="text-[15px] leading-[21px] text-[#959595]">{{ __('community.no_posts') }}</p>
                @endforelse
            </div>

            {{-- TODO: paginazione reale (come il "Vedi tutto" desktop) --}}
            @if ($visiblePosts !== [])
                <div class="mt-6 flex justify-center">
                    <flux:button class="!h-[39px] !w-[124px] !rounded-[20px] !border-0 !bg-[#0D171A] !text-sm !font-bold !text-white !shadow-none">{{ __('community.see_more') }}</flux:button>
                </div>
            @endif
        </div>
    </main>

    <div class="max-lg:hidden">
        @include('partials.site-footer')
    </div>

    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>

    @auth
    {{-- FAB 55px sopra la tabbar (XD: cerchio #6CD1EF a y648 su artboard 812) --}}
    <flux:modal.trigger name="scrivi-domanda">
        <flux:button variant="ghost" square aria-label="{{ __('community.open_composer') }}" class="!fixed !bottom-[109px] !right-4 !z-30 !h-[55px] !w-[55px] !rounded-full !bg-brand-cyan !text-ink !shadow-[0px_1px_5px_#00000033] hover:!bg-brand-cyan lg:!hidden">
            <flux:icon.chat class="h-[21px] w-[21px]" />
        </flux:button>
    </flux:modal.trigger>

    {{-- Composer a tutta pagina (XD "Scrivi domanda"). Solo mobile e aperto SOLO da
         flux:modal.trigger: aprirlo da PHP renderebbe inerte la pagina desktop (vedi CLAUDE.md).
         Il titolo del post resta derivato dal testo, come nel composer desktop. --}}
    <flux:modal name="scrivi-domanda" :closable="false" class="w-full !m-0 !max-h-none !min-h-dvh !max-w-full !rounded-none bg-white !px-4 !py-6 lg:hidden">
        <div class="flex items-center justify-between">
            <flux:heading level="2" class="!text-lg !font-semibold !leading-none !text-[#0D171A]">{{ __('community.write_question') }}</flux:heading>
            <flux:modal.close>
                <flux:button variant="ghost" size="sm" square aria-label="{{ __('community.close') }}" class="!rounded-full !text-[#555555]">
                    <flux:icon.close class="h-4 w-4" />
                </flux:button>
            </flux:modal.close>
        </div>

        <flux:separator class="!mt-4 !border-[#E9E9E9]" />

        <flux:textarea wire:model="composerBody" rows="8" resize="none" placeholder="{{ __('community.composer_placeholder') }}" class="!mt-4 !h-[200px] !w-full !rounded-none !border-0 !border-b !border-[#E9E9E9] !bg-transparent !px-0 !text-[15px] !text-[#0D171A] !shadow-none !ring-0 placeholder:!text-[#555555]" />

        <p class="mt-6 text-[15px] font-semibold leading-none text-[#0D171A]">{{ __('community.select_tags') }}</p>

        <flux:checkbox.group wire:model="composerTags" variant="pills" class="mt-4 flex flex-wrap items-center gap-2">
            @foreach ($tags as $tag)
                <flux:checkbox wire:key="composer-mobile-tag-{{ $tag }}" value="{{ $tag }}" label="{{ $tag }}" class="!h-[27px] !rounded-[3px] !bg-[#E9E9E9] !px-[10px] !text-sm !font-medium !text-[#959595] hover:!bg-[#E9E9E9] hover:!text-[#959595] {{ $tagPill[$tag] ?? '' }}" />
            @endforeach
        </flux:checkbox.group>

        <flux:button wire:click="publish" class="!mt-8 !h-[39px] !w-full !rounded-[19px] !border-0 !bg-[#0D171A] !text-[15px] !font-semibold !text-white !shadow-none">{{ __('community.share') }}</flux:button>
    </flux:modal>
    @endauth

    {{-- Pannello filtri a tutta pagina (XD "Filtri community" 375x812): testata col titolo
         centrato e la × a destra, riga #E9E9E9 a tutta larghezza, 7 tag a passo 40px e la
         pillola scura "Mostra N risultati" a 32px dal fondo. --}}
    <flux:modal name="filtri-community" :closable="false" class="!m-0 flex w-full !max-h-none !min-h-dvh !max-w-full flex-col !rounded-none bg-white !p-0 lg:hidden">
        {{-- pb-[4px]: nell'artboard il titolo non è centrato in colonna, sta 19px sotto l'inizio della testata --}}
        <div class="relative flex h-[62px] shrink-0 items-center justify-center pb-[4px]">
            <flux:heading level="2" class="!text-lg !font-semibold !leading-none !text-[#0D171A]">{{ __('community.filters_title') }}</flux:heading>

            <flux:modal.close>
                {{-- XD: × 10px di solo tratto #555555, a 16px dal bordo --}}
                <flux:button variant="ghost" size="sm" square aria-label="{{ __('community.close') }}" class="!absolute !right-[11px] !top-1/2 !-translate-y-1/2 !text-[#555555] hover:!bg-transparent hover:!text-[#555555]">
                    <flux:icon.close class="h-[10px] w-[10px]" stroke-width="2.5" />
                </flux:button>
            </flux:modal.close>
        </div>

        <flux:separator class="!border-[#E9E9E9]" />

        <div class="px-6">
            <h3 class="mt-[14px] text-lg font-semibold leading-none text-[#0D171A]">{{ __('community.filter_by_type') }}</h3>

            {{-- Righe a passo 40px = pallino 20 + gap 20: serve azzerare l'mb-3 che Flux mette
                 sui field dei gruppi. La label è un <ui-label>, quindi si veste da qui (un
                 selettore `label` sul checkbox non la raggiunge: è un fratello, non un figlio).
                 Il check XD è un tondo pieno #DEDEDE che da selezionato diventa ciano con la
                 spunta bianca. --}}
            <flux:checkbox.group wire:model.live="activeFilters" class="mt-[19px] flex flex-col gap-5 [&_[data-flux-field]]:!mb-0 [&_[data-flux-field]]:!gap-2 [&_[data-flux-label]]:!text-[15px] [&_[data-flux-label]]:!font-medium [&_[data-flux-label]]:!leading-5 [&_[data-flux-label]]:!text-[#0D171A]">
                @foreach ($tags as $tag)
                    <flux:checkbox wire:key="filtro-mobile-{{ $tag }}" value="{{ $tag }}" label="{{ $tag }}"
                        class="!mt-0 !size-5 [&_[data-flux-checkbox-indicator]]:!size-5 [&_[data-flux-checkbox-indicator]]:!rounded-full [&_[data-flux-checkbox-indicator]]:!border-0 [&_[data-flux-checkbox-indicator]]:!bg-[#DEDEDE] [&_[data-flux-checkbox-indicator]]:!shadow-none data-checked:[&_[data-flux-checkbox-indicator]]:!bg-brand-cyan [&_[data-flux-checkbox-indicator]_svg]:!text-white" />
                @endforeach
            </flux:checkbox.group>
        </div>

        <flux:modal.close class="mx-auto mb-8 mt-auto block w-[184px] pt-10">
            <flux:button class="!h-[39px] !w-full !rounded-[20px] !border-0 !bg-[#0D171A] !text-sm !font-bold !text-white !shadow-none">{{ trans_choice('community.show_results', count($visiblePosts), ['count' => count($visiblePosts)]) }}</flux:button>
        </flux:modal.close>
    </flux:modal>

    {{-- Sweet alert "Domanda condivisa con successo!" (XD "Community – sweet alert", card 315x224).
         Overlay e non <flux:modal>: è solo mobile, e showModal() su un dialog nascosto dal
         breakpoint bloccherebbe la pagina desktop (vedi CLAUDE.md). --}}
    @if ($shared)
        <div class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('community.shared_title') }}" x-data @keydown.escape.window="$wire.dismissShared()">
            <div class="absolute inset-0 bg-black/40" wire:click="dismissShared" aria-hidden="true"></div>

            <div class="pointer-events-none absolute inset-0 flex items-center justify-center px-4">
                {{-- Ritmo XD: 21 sopra l'icona, 11 icona→titolo, 11 titolo→testo. Il fondo è 28
                     invece di 49 perché il testo qui occupa 3 righe e non 2 (la prima frase
                     misura 341px nel nostro Nunito, non entra nei 315 della card): così la
                     card resta alta 224 come l'artboard. --}}
                <div class="pointer-events-auto relative w-full max-w-[315px] rounded-[4px] border border-[#43BA6C]/50 bg-[#EAFFF1] px-4 pb-[26px] pt-[21px]">
                    <flux:button variant="ghost" size="sm" square wire:click="dismissShared" aria-label="{{ __('community.close') }}" class="!absolute !right-1 !top-1 !text-[#0D171A] hover:!bg-transparent">
                        <flux:icon.close class="h-4 w-4" />
                    </flux:button>

                    <flux:icon.check-circle class="mx-auto h-[38px] w-[38px] text-[#43BA6C]" stroke-width="3" />

                    {{-- XD va a capo dopo "condivisa" e dopo "community.": larghezze limitate
                         invece di <br> nelle traduzioni (le chiavi restano frasi intere) --}}
                    <p class="mx-auto mt-[11px] max-w-[170px] text-center text-lg font-bold leading-[26px] text-[#43BA6C]">{{ __('community.shared_title') }}</p>
                    <p class="mx-auto mt-[11px] text-center text-[15px] leading-[21px] text-[#555555]">{{ __('community.shared_body') }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
