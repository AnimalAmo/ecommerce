<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

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
