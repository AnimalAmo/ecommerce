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
        {{-- 1. Hero foto full-bleed con card composer in vetro smerigliato sopra la foto --}}
        <section class="relative h-[524px] w-full overflow-hidden">
            <img src="{{ asset('img/xd/community-hero.jpg') }}" alt="{{ __('community.hero_alt') }}" class="absolute inset-0 h-full w-full object-cover">

            <div class="{{ $px }} relative h-full">
                <div class="mx-auto w-full max-w-[1062px] pt-[102px]">
                    <h1 class="text-4xl font-bold leading-none text-white">{{ __('community.hero_title') }}</h1>
                    <p class="mt-[28px] text-lg leading-[25px] text-white">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut l voluptua.</p>

                    {{-- Card composer (XD "Rettangolo 635": blur 7, brightness +10, fill opacity 0); padding 16/24, altezza fluida così il bottone resta dentro i padding --}}
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
                </div>
            </div>
        </section>

        <div class="{{ $px }} pb-[120px] pt-10">
            <div class="mx-auto w-full max-w-[1062px]">
                {{-- 2. Tab Tutti i post / I miei post (switch Livewire, deep-link ?tab=miei) --}}
                <nav class="flex items-end gap-[41px] border-b border-[#DEDEDE]" aria-label="{{ __('community.sections_aria') }}">
                    <flux:button variant="ghost" wire:click="switchTab('tutti')" :aria-current="$tab === 'tutti' ? 'page' : null" class="relative !h-auto !rounded-none !p-0 !pb-[11px] !text-lg !font-medium hover:!bg-transparent {{ $tab === 'tutti' ? '!text-[#2B2B2B] hover:!text-[#2B2B2B]' : '!text-[#959595]' }}">
                        {{ __('community.tab_all') }}
                        @if ($tab === 'tutti')
                            <span class="absolute inset-x-0 bottom-0 h-[2.5px] translate-y-[1.25px] bg-[#2B2B2B]" aria-hidden="true"></span>
                        @endif
                    </flux:button>
                    <flux:button variant="ghost" wire:click="switchTab('miei')" :aria-current="$tab === 'miei' ? 'page' : null" class="relative !h-auto !rounded-none !p-0 !pb-[11px] !text-lg !font-medium hover:!bg-transparent {{ $tab === 'miei' ? '!text-[#2B2B2B] hover:!text-[#2B2B2B]' : '!text-[#959595]' }}">
                        {{ __('community.tab_mine') }}
                        @if ($tab === 'miei')
                            <span class="absolute inset-x-0 bottom-0 h-[2.5px] translate-y-[1.25px] bg-[#2B2B2B]" aria-hidden="true"></span>
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
                            <p class="mt-[13px] whitespace-pre-line text-[15px] leading-[21px] text-[#2B2B2B]">{{ $post['body'] }}</p>

                            @foreach ($post['replies'] as $reply)
                                <div wire:key="post-{{ $post['id'] }}-reply-{{ $loop->index }}" class="mt-[22px]">
                                    <p class="text-[15px] font-bold leading-[21px] text-[#959595]">{{ $reply['author'] }}</p>
                                    <p class="mt-[13px] text-[15px] leading-[21px] text-[#2B2B2B]">{{ $reply['body'] }}</p>
                                </div>
                            @endforeach

                            {{-- Riga risposta: input pill con bottone "Rispondi" dentro il bordo destro --}}
                            <div class="relative mt-[26px]">
                                <flux:input type="text" wire:model="replyDrafts.{{ $post['id'] }}" wire:keydown.enter="reply({{ $post['id'] }})" placeholder="{{ __('community.composer_placeholder') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!rounded-full [&_input]:!border [&_input]:!border-[#E9E9E9] [&_input]:!bg-white [&_input]:!pl-6 [&_input]:!pr-[135px] [&_input]:!text-[15px] [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:italic [&_input]:placeholder:text-[#959595]" />
                                <flux:button wire:click="reply({{ $post['id'] }})" class="!absolute !right-0 !top-0 !h-10 !w-[127px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none">{{ __('community.reply') }}</flux:button>
                            </div>
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
    </main>

    @include('partials.site-footer')
</div>
