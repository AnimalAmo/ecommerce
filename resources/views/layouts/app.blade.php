<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @php
            // Il <head> SEO vive qui e non nei componenti: il layout è uno solo e
            // le pagine sono decine di componenti Livewire. Il titolo lo calcolano
            // loro con ->title(...), tutto il resto lo deriva App\Support\Seo dalla
            // rotta corrente (description) e dalla request (URL), mai da un host cablato.
            $metaTitle = $title ?? config('app.name');
            $metaDescription = \App\Support\Seo::description();

            // L'area partner viaggia già con X-Robots-Tag: noindex
            // (App\Http\Middleware\NoIndexPartnerPages). Canonical, hreflang e
            // Open Graph sono inviti all'indicizzazione: lì contraddirebbero
            // l'header, quindi il blocco non si stampa proprio.
            $indexable = \App\Support\Seo::isIndexable();
            $canonical = $indexable ? \App\Support\Seo::canonical() : null;
            $alternates = $indexable ? \App\Support\Seo::alternates() : [];
            $defaultAlternate = $alternates[\App\Support\Seo::defaultLocale()] ?? null;
        @endphp

        <title>{{ $metaTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">

        @if ($indexable)
            {{-- Una sola URL per pagina: filtri e ?utm_* non devono generare duplicati. --}}
            <link rel="canonical" href="{{ $canonical }}">

            {{-- hreflang: dice a Google che it ed en sono la STESSA pagina in due
                 lingue, non due pagine in concorrenza. Gli URL li costruisce mcamara,
                 perché gli slug sono tradotti (chi-siamo ↔ about-us). --}}
            @foreach ($alternates as $alternateLocale => $alternateUrl)
                <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternateUrl }}">
            @endforeach
            @if ($defaultAlternate !== null)
                <link rel="alternate" hreflang="x-default" href="{{ $defaultAlternate }}">
            @endif

            {{-- Open Graph: senza questi tag ogni condivisione su Facebook,
                 WhatsApp o LinkedIn esce come card vuota, solo URL. --}}
            <meta property="og:type" content="website">
            <meta property="og:site_name" content="{{ config('app.name') }}">
            <meta property="og:title" content="{{ $metaTitle }}">
            <meta property="og:description" content="{{ $metaDescription }}">
            <meta property="og:url" content="{{ $canonical }}">
            <meta property="og:locale" content="{{ \App\Support\Seo::regional(app()->getLocale()) }}">
            @foreach (\App\Support\Seo::alternateRegionals() as $alternateRegional)
                <meta property="og:locale:alternate" content="{{ $alternateRegional }}">
            @endforeach
            <meta property="og:image" content="{{ \App\Support\Seo::image() }}">
            <meta property="og:image:width" content="{{ \App\Support\Seo::SHARE_IMAGE_WIDTH }}">
            <meta property="og:image:height" content="{{ \App\Support\Seo::SHARE_IMAGE_HEIGHT }}">

            {{-- X legge gli og:*, gli serve solo sapere che la card è quella grande. --}}
            <meta name="twitter:card" content="summary_large_image">
        @endif

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
        <link rel="icon" type="image/png" href="{{ asset('favicon-32.png') }}" sizes="32x32">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        {{-- Widget Iubenda (privacy e cookie): in <head> e prima degli altri
             script, così può bloccare i cookie non essenziali fino al consenso. --}}
        <script type="text/javascript" src="https://embeds.iubenda.com/widgets/f982b4fa-cef8-48b4-86c6-a9d12f07b263.js"></script>

        {{ \Illuminate\Support\Facades\Vite::fonts() }}
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
        @fluxAppearance
    </head>
    <body>
        {{ $slot }}

        {{-- Modali auth raggiungibili dall'header + toast globale ("Modifiche salvate.", invisibile
             finché non mostrato): istanze uniche per pagina, prima degli script come da Flux --}}
        <livewire:auth.auth-modal />
        <livewire:auth.register-modal />
        <livewire:auth.partner-login-modal />
        <livewire:auth.forgot-password-modal />
        <flux:toast />

        @livewireScripts
        @fluxScripts

        {{-- Loader Iubenda (snippet ufficiale): fa aprire l'informativa cookie dei
             footer in un riquadro sopra il sito invece di portare fuori. Si carica
             da solo sull'evento load, quindi non ritarda il primo render. --}}
        <script type="text/javascript">(function (w,d) {var loader = function () {var s = d.createElement("script"), tag = d.getElementsByTagName("script")[0]; s.src="https://cdn.iubenda.com/iubenda.js"; tag.parentNode.insertBefore(s,tag);}; if(w.addEventListener){w.addEventListener("load", loader, false);}else if(w.attachEvent){w.attachEvent("onload", loader);}else{w.onload = loader;}})(window, document);</script>

        <script src="//code.tidio.co/lxyeqk5ehbrcahqlghaaeqmsdxzo9ztn.js" async></script>
    </body>
</html>
