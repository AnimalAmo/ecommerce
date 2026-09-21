{{-- Pagina libera creata dal pannello: stessa impaginazione e tipografia delle pagine legali. --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pt-[60px] pb-20 max-lg:pt-8 max-lg:pb-10">
            <div class="mx-auto w-full max-w-[900px]">
                <h1 class="text-4xl font-bold text-black max-lg:text-[18px] max-lg:leading-[21px] max-lg:text-[#0D171A]">
                    {{ $page->titleFor() }}
                </h1>

                {{-- Corpo non escapato: HTML filtrato da HtmlSanitizer al salvataggio dal pannello. --}}
                <div class="legal-content mt-8 max-lg:mt-6">{!! $page->bodyFor() !!}</div>
            </div>
        </div>
    </main>

    @include('partials.site-footer')
</div>
