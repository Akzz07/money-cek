<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Monetra') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900 selection:bg-blue-500 selection:text-white">
        <!-- Centered Mobile Container -->
        <div class="min-h-screen max-w-md mx-auto bg-slate-50 relative flex flex-col shadow-2xl border-x border-slate-200/80 pb-20">
            <!-- Main Content Area -->
            <main class="flex-1 w-full">
                {{ $slot }}
            </main>

            <!-- Bottom Navigation Bar -->
            <x-ui.bottom-nav :active="$activeNav ?? 'home'" />
        </div>
    </body>
</html>

