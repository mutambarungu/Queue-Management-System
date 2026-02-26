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

</head>

<body class="nk-body bg-lighter npc-general has-sidebar app-responsive">
    <div class="nk-app-root">
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
    </div>
    <script src="{{ asset('assets/js/bundlee1e3.js?ver=3.2.4') }}"></script>
    <script src="{{ asset('assets/js/scriptse1e3.js?ver=3.2.4') }}"></script>
    <script src="{{ asset('assets/js/demo-settingse1e3.js?ver=3.2.4') }}"></script>
    <script src="{{ asset('assets/js/charts/gd-defaulte1e3.js?ver=3.2.4') }}"></script>
    <script src="{{ asset('assets/js/libs/datatable-btnse1e3.js?ver=3.2.4') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
