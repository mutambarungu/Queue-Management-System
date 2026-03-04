<?php if (!function_exists('activeClass')) {
    function activeClass($routeNames, $class = 'active')
    {
        if (is_array($routeNames)) {
            foreach ($routeNames as $route) {
                if (request()->routeIs($route)) {
                    return $class;
                }
            }
        } else {
            if (request()->routeIs($routeNames)) {
                return $class;
            }
        }
        return '';
    }
} ?>

@php $role = Auth::user()->role; @endphp

<div class="nk-sidebar nk-sidebar-fixed is-dark" data-content="sidebarMenu">
    <div class="nk-sidebar-element nk-sidebar-head">
        <div class="nk-menu-trigger">
            <a href="#" class="nk-nav-toggle nk-quick-nav-icon d-xl-none" data-target="sidebarMenu">
                <em class="icon ni ni-arrow-left"></em>
            </a>
            <a href="#" class="nk-nav-compact nk-quick-nav-icon d-none d-xl-inline-flex" data-target="sidebarMenu">
                <em class="icon ni ni-menu"></em>
            </a>
        </div>
        <div class="nk-sidebar-brand">
            <a href="#" class="logo-link nk-sidebar-logo">
                <img src="{{ asset('logo_unilak.jfif') }}"
                    alt="Digital Queue Logo"
                    width="40" height="40">
            </a>
        </div>
    </div>

    <div class="nk-sidebar-element nk-sidebar-body">
        <div class="nk-sidebar-content">
            <div class="nk-sidebar-menu" data-simplebar>
                <ul class="nk-menu">

                    {{-- Dashboard (All Roles) --}}
                    <li class="nk-menu-item">
                        <a href="{{ route('dashboard') }}" class="nk-menu-link {{ activeClass('dashboard') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-user-list"></em></span>
                            <span class="nk-menu-text">Dashboard</span>
                        </a>
                    </li>

                    {{-- Admin Menus --}}
                    @if($role === 'admin')
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Admin</h6>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('admin.users.index') }}" class="nk-menu-link {{ activeClass('admin.users.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-users"></em></span>
                            <span class="nk-menu-text">Users</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('admin.offices.index') }}" class="nk-menu-link {{ activeClass('admin.offices.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-building"></em></span>
                            <span class="nk-menu-text">Offices</span>
                        </a>
                    </li>
                    <li class="nk-menu-item">
                        <a href="{{ route('admin.offices.qrcodes') }}" class="nk-menu-link {{ activeClass('admin.offices.qrcodes') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-building"></em></span>
                            <span class="nk-menu-text">Office QR Codes</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('admin.staff.index') }}" class="nk-menu-link {{ activeClass('admin.staff.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-users"></em></span>
                            <span class="nk-menu-text">Staff</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('admin.students.index') }}" class="nk-menu-link {{ activeClass('admin.students.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-users"></em></span>
                            <span class="nk-menu-text">Students</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('admin.service-types.index') }}" class="nk-menu-link {{ activeClass('admin.service-types.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-list-check"></em></span>
                            <span class="nk-menu-text">service types</span>
                        </a>
                    </li>
                    <li class="nk-menu-item">
                        <a href="{{ route('admin.requests.index') }}" class="nk-menu-link {{ activeClass('admin.requests.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-list-check"></em></span>
                            <span class="nk-menu-text">All Requests</span>
                        </a>
                    </li>
                    <li class="nk-menu-item">
                        <a href="{{ route('admin.requests.archived') }}"
                            class="nk-menu-link {{ activeClass('admin.requests.archived') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-archive"></em></span>
                            <span class="nk-menu-text">Archived Requests</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('admin.appointments.index') }}" class="nk-menu-link {{ activeClass('admin.appointments.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-calendar-check"></em></span>
                            <span class="nk-menu-text">Appointments</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('admin.faqs.index') }}" class="nk-menu-link {{ activeClass('admin.faqs.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-help"></em></span>
                            <span class="nk-menu-text">FAQs</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('admin.reports.index') }}" class="nk-menu-link {{ activeClass('admin.reports.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-report"></em></span>
                            <span class="nk-menu-text">Reports</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('admin.queue-calendar.index') }}" class="nk-menu-link {{ activeClass('admin.queue-calendar.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-calendar"></em></span>
                            <span class="nk-menu-text">Queue Calendar</span>
                        </a>
                    </li>

                    @endif

                    {{-- Staff Menus --}}
                    @if($role === 'staff')
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Staff</h6>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('staff.service-types.index') }}" class="nk-menu-link {{ activeClass('staff.service-types.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-list-check"></em></span>
                            <span class="nk-menu-text">service types</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('staff.requests.index') }}" class="nk-menu-link {{ activeClass('staff.requests.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-list-check"></em></span>
                            <span class="nk-menu-text">Assigned Requests</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('staff.appointments.index') }}" class="nk-menu-link {{ activeClass('staff.appointments.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-calendar-check"></em></span>
                            <span class="nk-menu-text">Appointments</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('staff.faqs.index') }}" class="nk-menu-link {{ activeClass('staff.faqs.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-help"></em></span>
                            <span class="nk-menu-text">FAQ</span>
                        </a>
                    </li>
                    @endif

                    {{-- Student Menus --}}
                    @if($role === 'student')
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Student</h6>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('student.faq.index') }}" class="nk-menu-link {{ activeClass('student.faq.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-help"></em></span>
                            <span class="nk-menu-text">FAQ</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('student.requests.index') }}" class="nk-menu-link {{ activeClass('student.requests.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-list-check"></em></span>
                            <span class="nk-menu-text">My Requests</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('student.requests.create') }}" class="nk-menu-link {{ activeClass('student.requests.create') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-plus-circle"></em></span>
                            <span class="nk-menu-text">Submit Request</span>
                        </a>
                    </li>

                    <li class="nk-menu-item">
                        <a href="{{ route('student.appointments.index') }}" class="nk-menu-link {{ activeClass('student.appointments.*') }}">
                            <span class="nk-menu-icon"><em class="icon ni ni-calendar-check"></em></span>
                            <span class="nk-menu-text">My Appointments</span>
                        </a>
                    </li>
                    @endif

                </ul>
            </div>
        </div>
    </div>
</div>
