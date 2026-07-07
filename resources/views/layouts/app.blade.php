<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

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
        <flux:toast />

        @livewireScripts
        @fluxScripts
    </body>
</html>
