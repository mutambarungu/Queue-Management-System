<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name', 'University Queue') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/css/dashlitee1e3.css?ver=3.2.4') }}">
    <link id="skin-default" rel="stylesheet" href="{{ asset('assets/css/themee1e3.css?ver=3.2.4') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/responsive-fixes.css') }}">
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
@php($isQueueJoinPage = request()->routeIs('student.queue.join.form') || request()->routeIs('queue.join.form'))

<body class="nk-body bg-lighter npc-general has-sidebar app-responsive">
    <div class="nk-app-root">
        @if($isQueueJoinPage)
            <div class="nk-main">
                <div class="nk-wrap">
                    <div class="nk-content">
                        @yield('content')
                    </div>
                </div>
            </div>
        @else
            <div class="nk-main ">
                @include('layouts.sidebar')
                <div class="nk-wrap ">
                    @include('layouts.header')

                    <!-- Page Content -->
                    <div class="nk-content ">
                        @yield('content')
                    </div>

                    <div class="nk-footer">
                        <div class="container-fluid">
                            <div class="nk-footer-wrap">
                                <div class="nk-footer-copyright"> &copy; {{ date('Y') }} <a
                                        href="#" target="_blank">{{ config('app.name', 'University Queue') }}</a></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <script src="{{ asset('assets/js/bundlee1e3.js?ver=3.2.4') }}"></script>
    @if(!$isQueueJoinPage)
        <script src="{{ asset('assets/js/scriptse1e3.js?ver=3.2.4') }}"></script>
        <script src="{{ asset('assets/js/demo-settingse1e3.js?ver=3.2.4') }}"></script>
        <script src="{{ asset('assets/js/charts/gd-defaulte1e3.js?ver=3.2.4') }}"></script>
        <script src="{{ asset('assets/js/libs/datatable-btnse1e3.js?ver=3.2.4') }}"></script>
        @include('layouts.realtime-scripts')
    @endif
    <script>
        (function () {
            const saved = localStorage.getItem('uqs-theme');
            const isDark = saved === 'dark';
            document.documentElement.classList.toggle('dark-mode', isDark);
            document.body.classList.toggle('dark-mode', isDark);
        })();

        function syncThemeToggleIcon() {
            const toggle = document.getElementById('themeToggle');
            if (!toggle) return;
            const icon = toggle.querySelector('em');
            if (!icon) return;
            const isDark = document.body.classList.contains('dark-mode');
            icon.className = isDark ? 'icon ni ni-sun-fill' : 'icon ni ni-moon-fill';
            toggle.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        }

        document.addEventListener('click', function (event) {
            const toggle = event.target.closest('#themeToggle');
            if (!toggle) return;

            event.preventDefault();
            const willBeDark = !document.body.classList.contains('dark-mode');
            document.body.classList.toggle('dark-mode', willBeDark);
            document.documentElement.classList.toggle('dark-mode', willBeDark);
            localStorage.setItem('uqs-theme', willBeDark ? 'dark' : 'light');
            syncThemeToggleIcon();
        });

        document.addEventListener('DOMContentLoaded', function () {
            syncThemeToggleIcon();
            const tables = document.querySelectorAll('.nk-content table');

            tables.forEach((table) => {
                if (table.closest('.table-responsive') || table.closest('.dataTables_wrapper')) {
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            });
        });
    </script>
</body>

</html>
