{{-- Pagina legale (termini e condizioni clienti/fornitori): corpo HTML dal DB, footer minimal come le altre pagine secondarie --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pt-[60px] pb-20 max-lg:pt-8 max-lg:pb-10">
            <div class="mx-auto w-full max-w-[900px]">
                <h1 class="text-4xl font-bold text-black max-lg:text-[18px] max-lg:leading-[21px] max-lg:text-[#0D171A]">
                    {{ $page->titleFor() }}
                </h1>

                @if ($page->last_updated_at)
                    <p class="mt-3 text-sm text-gray-600">
                        {{ __('legal.last_updated') }}: {{ $page->last_updated_at->format('d/m/Y') }}
                    </p>
                @endif

                @if (app()->getLocale() !== \App\Models\Page\Page::SOURCE_LOCALE)
                    <p class="mt-4 rounded-[3px] bg-gray-100 px-4 py-3 text-sm text-ink-700">
                        {{ __('legal.binding_language') }}
                    </p>
                @endif

                {{-- Corpo non escapato: HTML strutturato prodotto dal nostro seeder,
                     non input utente. Col CRUD di backoffice servirà sanitizzare. --}}
                <div class="legal-content mt-8 max-lg:mt-6">{!! $page->bodyFor() !!}</div>
            </div>
        </div>
    </main>

    @include('partials.footer-minimal')
</div>
