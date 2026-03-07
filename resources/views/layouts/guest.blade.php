<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('assets/css/dark-mode-overrides.css') }}">
        <script>
            (function () {
                const mode = localStorage.getItem('uqs-theme');
                if (mode === 'dark') {
                    document.documentElement.classList.add('dark-mode');
                }
            })();
        </script>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <button id="themeToggleGuest" class="theme-toggle-btn" style="position:fixed;top:12px;right:12px;z-index:1200;border:1px solid #ced4da;background:#fff;">
            <span id="themeToggleGuestIcon">🌙</span>
        </button>
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
        <script>
            (function () {
                const isDark = localStorage.getItem('uqs-theme') === 'dark';
                document.body.classList.toggle('dark-mode', isDark);
                document.documentElement.classList.toggle('dark-mode', isDark);
                const icon = document.getElementById('themeToggleGuestIcon');
                if (icon) icon.textContent = isDark ? '☀️' : '🌙';
            })();

            document.getElementById('themeToggleGuest')?.addEventListener('click', function () {
                const willBeDark = !document.body.classList.contains('dark-mode');
                document.body.classList.toggle('dark-mode', willBeDark);
                document.documentElement.classList.toggle('dark-mode', willBeDark);
                localStorage.setItem('uqs-theme', willBeDark ? 'dark' : 'light');
                document.getElementById('themeToggleGuestIcon').textContent = willBeDark ? '☀️' : '🌙';
            });
        </script>
    </body>
</html>
