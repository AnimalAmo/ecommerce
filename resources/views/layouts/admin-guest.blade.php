<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ isset($title) ? $title.' · ' : '' }}{{ __('admin.brand') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen font-sans text-ink antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center gap-7 bg-[linear-gradient(296deg,#FF3EA526_0%,#68CDEB33_100%)] px-5 py-12">
            <div class="flex flex-col items-center gap-2.5">
                <img src="{{ asset('img/logo.svg') }}" alt="{{ __('admin.logo_alt') }}" class="block h-auto w-[108px]">
                <span class="flex items-center gap-[7px] text-admin-teal">
                    <flux:icon.animal class="size-[15px] shrink-0" />
                    <span class="text-[10.5px] font-bold tracking-[.11em] uppercase">{{ __('admin.section') }}</span>
                </span>
            </div>

            <div class="w-full max-w-[480px] rounded-[3px] border border-gray-150 bg-white px-9 py-10 shadow-[0_1px_10px_rgba(0,0,0,.1)] max-sm:px-6">
                {{ $slot }}
            </div>
        </div>

        @livewireScripts
        @fluxScripts
    </body>
</html>
