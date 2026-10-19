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

        <!-- Theme Initialization to prevent FOUC -->
        <script>
            if (localStorage.getItem('monetra_theme') === 'dark' || (!('monetra_theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-sky-500 selection:text-white transition-colors duration-200">
        <!-- Centered Mobile Container with Variant 5 Blue Sky Ambient Glow -->
        <div class="min-h-screen max-w-md mx-auto bg-gradient-to-b from-sky-100/70 via-sky-50/30 to-slate-50 dark:from-[#0B1528] dark:via-slate-900 dark:to-slate-950 relative flex flex-col shadow-2xl border-x border-slate-200/80 dark:border-slate-800/80 pb-20 transition-colors duration-200">
            <!-- Main Content Area -->
            <main class="flex-1 w-full">
                {{ $slot }}
            </main>

            <!-- Bottom Navigation Bar -->
            <x-ui.bottom-nav :active="$activeNav ?? 'home'" />
        </div>
    </body>
</html>

