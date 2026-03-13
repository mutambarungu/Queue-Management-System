@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<style>
    .dash-live-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 1rem;
    }

    .live-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(25, 135, 84, 0.14);
        border: 1px solid rgba(25, 135, 84, 0.35);
        color: #198754;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .live-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #198754;
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7);
        animation: livePulse 1.8s infinite;
    }

    .live-dot.reconnecting {
        background: #fd7e14;
        box-shadow: 0 0 0 0 rgba(253, 126, 20, 0.7);
    }

    .dash-event {
        min-height: 1.2rem;
        font-size: 0.88rem;
        color: #0d6efd;
        opacity: 0;
        transform: translateY(-4px);
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .dash-event.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .metric-value {
        transition: transform 0.25s ease, filter 0.25s ease;
    }

    .metric-value.bump {
        transform: scale(1.08);
        filter: brightness(1.15);
    }

    @keyframes livePulse {
        70% { box-shadow: 0 0 0 12px rgba(25, 135, 84, 0); }
        100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
    }

    @media (prefers-reduced-motion: reduce) {
        .live-dot,
        .metric-value {
            animation: none !important;
            transition: none !important;
        }
    }

    .student-queue-card .token-pill {
        display: inline-block;
        padding: 0.5rem 0.85rem;
        border-radius: 12px;
        background: linear-gradient(140deg, #0d6efd, #14b8a6);
        border: 1px solid rgba(13, 110, 253, 0.38);
        color: #fff;
        font-weight: 700;
        font-size: 1.15rem;
        letter-spacing: .04em;
        box-shadow: 0 8px 20px rgba(13, 110, 253, .28);
    }
    .token-pill.token-highlight {
        animation: tokenGlow 1.8s ease-in-out infinite;
    }
    .student-queue-card {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
    }
    .student-queue-glow {
        position: relative;
        background: linear-gradient(145deg, #0f172a, #1e3a8a);
        color: #fff;
        border-radius: 14px;
        padding: 14px 16px;
        overflow: hidden;
    }
    .student-queue-glow::after {
        content: "";
        position: absolute;
        inset: -120% -30%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.28), transparent 60%);
        animation: liveSweep 3.2s linear infinite;
        pointer-events: none;
    }
    .student-queue-meta {
        position: relative;
        z-index: 1;
    }
    .tracker-live-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .65rem;
        flex-wrap: wrap;
    }
    .tracker-live-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(34, 197, 94, 0.2);
        border: 1px solid rgba(125, 255, 182, 0.4);
        font-weight: 600;
    }
    .tracker-live-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #7dffb6;
        box-shadow: 0 0 0 0 rgba(125, 255, 182, 0.75);
        animation: livePulse 1.8s infinite;
    }
    .tracker-live-dot.reconnecting {
        background: #ffc078;
        box-shadow: 0 0 0 0 rgba(255, 192, 120, 0.75);
    }
    .tracker-sound-switch {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: 5px 10px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, .26);
        background: rgba(255, 255, 255, .08);
    }
    .tracker-sound-switch .form-check-input {
        margin-top: 0;
    }
    .tracker-sound-switch .form-check-label {
        color: #fff;
        font-size: .82rem;
        font-weight: 600;
    }
    .queue-heartbeat {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #7dffb6;
        box-shadow: 0 0 0 0 rgba(125, 255, 182, 0.75);
        animation: livePulse 1.8s infinite;
        display: inline-block;
        margin-right: 7px;
    }
    .queue-progress {
        height: 10px;
        border-radius: 999px;
        background: #e9eef5;
        overflow: hidden;
    }
    .queue-progress-bar {
        height: 100%;
        width: 0;
        background: linear-gradient(90deg, #2563eb, #06b6d4, #22c55e);
        transition: width .45s ease;
    }
    .queue-empty-live {
        border: 1px dashed rgba(13, 110, 253, 0.35);
        border-radius: 12px;
        padding: 1rem;
        background: rgba(13, 110, 253, 0.03);
    }
    .queue-radar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        border: 2px solid rgba(13, 110, 253, 0.5);
        position: relative;
        margin-bottom: .55rem;
    }
    .queue-radar::before,
    .queue-radar::after {
        content: "";
        position: absolute;
        inset: 8px;
        border: 2px solid rgba(13, 110, 253, 0.35);
        border-radius: 50%;
    }
    .queue-radar::after {
        animation: radarPing 1.8s ease-out infinite;
    }
    .tracker-side {
        position: sticky;
        top: 1rem;
    }
    @keyframes liveSweep {
        0% { transform: translateX(-10%); }
        100% { transform: translateX(12%); }
    }
    @keyframes radarPing {
        from { transform: scale(1); opacity: 0.85; }
        to { transform: scale(1.8); opacity: 0; }
    }
    @keyframes tokenGlow {
        0%,100% { box-shadow: 0 8px 20px rgba(13, 110, 253, .3), 0 0 0 0 rgba(20, 184, 166, .45); }
        50% { box-shadow: 0 12px 26px rgba(13, 110, 253, .4), 0 0 0 12px rgba(20, 184, 166, 0); }
    }

    .student-dashboard {
        --dash-ink: #0f172a;
        --dash-muted: #64748b;
        --dash-muted-2: #94a3b8;
        --dash-accent: #2563eb;
        --dash-card: #ffffff;
        --dash-border: rgba(15, 23, 42, 0.08);
        --dash-shadow: 0 24px 50px rgba(15, 23, 42, 0.08);
        --dash-soft: rgba(37, 99, 235, 0.08);
        color: var(--dash-ink);
    }
    .dark-mode .student-dashboard {
        --dash-ink: #e2e8f0;
        --dash-muted: #94a3b8;
        --dash-muted-2: #64748b;
        --dash-card: rgba(15, 23, 42, 0.92);
        --dash-border: rgba(148, 163, 184, 0.18);
        --dash-shadow: 0 24px 50px rgba(2, 6, 23, 0.45);
        --dash-soft: rgba(56, 189, 248, 0.08);
    }
    .premium-hero {
        position: relative;
        border-radius: 24px;
        padding: 24px;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 58, 138, 0.92), rgba(37, 99, 235, 0.88));
        color: #f8fafc;
        border: 1px solid rgba(148, 163, 184, 0.25);
        overflow: hidden;
    }
    .premium-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 18% 8%, rgba(255, 255, 255, 0.08), transparent 45%),
            radial-gradient(circle at 85% 90%, rgba(0, 0, 0, 0.35), transparent 55%),
            repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0, rgba(255, 255, 255, 0.03) 1px, transparent 1px, transparent 6px);
        opacity: 0.7;
        pointer-events: none;
        z-index: 0;
    }
    .premium-hero::after {
        content: "";
        position: absolute;
        inset: -80% 20%;
        background: linear-gradient(120deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0));
        transform: translateX(-30%);
        animation: heroSheen 5.5s linear infinite;
        pointer-events: none;
        z-index: 0;
    }
    .premium-hero > * {
        position: relative;
        z-index: 1;
    }
    @keyframes heroSheen {
        0% { transform: translateX(-40%) rotate(8deg); opacity: 0.2; }
        50% { opacity: 0.35; }
        100% { transform: translateX(45%) rotate(8deg); opacity: 0.15; }
    }
    @keyframes dateShimmer {
        0% { transform: translateX(0); opacity: 0; }
        20% { opacity: 0.4; }
        60% { opacity: 0.2; }
        100% { transform: translateX(240%); opacity: 0; }
    }
    @keyframes countdownFlip {
        0% { transform: translateY(6px) scale(0.96); opacity: 0.6; }
        55% { transform: translateY(-4px) scale(1.05); opacity: 1; }
        100% { transform: translateY(0) scale(1); opacity: 1; }
    }
    .welcome-strip {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
        gap: 24px;
        align-items: center;
    }
    .welcome-content {
        position: relative;
        z-index: 1;
    }
    .welcome-date {
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(226, 232, 240, 0.65);
        margin-bottom: 8px;
        display: inline-flex;
        position: relative;
        overflow: hidden;
    }
    .welcome-date::after {
        content: "";
        position: absolute;
        top: 0;
        bottom: 0;
        left: -35%;
        width: 35%;
        background: linear-gradient(120deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0));
        opacity: 0.6;
        animation: dateShimmer 6.5s ease-in-out infinite;
        pointer-events: none;
    }
    .welcome-title {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 6px;
        color: #f8fafc;
        text-shadow: 0 10px 26px rgba(15, 23, 42, 0.35);
    }
    .welcome-sub {
        font-size: 0.95rem;
        color: rgba(248, 250, 252, 0.72);
        margin-bottom: 16px;
        max-width: 460px;
    }
    .welcome-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .welcome-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.18);
        font-size: 0.8rem;
        font-weight: 600;
        color: rgba(248, 250, 252, 0.92);
    }
    .welcome-art {
        position: relative;
        min-height: clamp(220px, 26vw, 280px);
        border-radius: 20px;
        background: transparent;
        border: none;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        animation: welcomeFloat 6s ease-in-out infinite;
        z-index: 1;
    }
    .welcome-art.has-images .welcome-art-placeholder {
        opacity: 0;
        transform: translateY(6px);
        pointer-events: none;
    }
    .welcome-art-track {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        width: 100%;
        height: 100%;
        padding: 8px 0;
    }
    .welcome-art-track::before {
        content: "";
        position: absolute;
        inset: 8% 0;
        background: radial-gradient(circle at 70% 55%, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0) 60%),
            linear-gradient(90deg, rgba(15, 23, 42, 0.05), rgba(15, 23, 42, 0.32));
        opacity: 0.55;
        pointer-events: none;
        z-index: 0;
    }
    .welcome-art-image {
        display: block;
        position: relative;
        z-index: 1;
        width: auto;
        height: clamp(190px, 24vw, 250px);
        max-width: 92%;
        object-fit: contain;
        filter: drop-shadow(0 20px 34px rgba(15, 23, 42, 0.22));
        animation: artFloat 6.5s ease-in-out infinite;
    }
    .welcome-art-placeholder {
        position: relative;
        z-index: 1;
        text-align: center;
        font-size: 0.9rem;
        font-weight: 600;
        color: rgba(248, 250, 252, 0.92);
        transition: opacity 0.35s ease, transform 0.35s ease;
    }
    .welcome-art-placeholder span {
        display: block;
        font-size: 0.75rem;
        font-weight: 500;
        color: rgba(248, 250, 252, 0.7);
        margin-top: 6px;
    }
    .snapshot-card,
    .appointment-card {
        background: var(--dash-card);
        border: 1px solid var(--dash-border);
        border-radius: 20px;
        padding: 20px;
        box-shadow: var(--dash-shadow);
    }
    .section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 14px;
    }
    .section-head h4 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--dash-ink);
    }
    .snapshot-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
    }
    .snapshot-tile {
        border-radius: 16px;
        padding: 16px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.9), rgba(226, 232, 240, 0.55));
        box-shadow: inset 0 2px 0 rgba(37, 99, 235, 0.16);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border 0.3s ease;
    }
    .dark-mode .snapshot-tile {
        background: rgba(15, 23, 42, 0.7);
        border-color: rgba(148, 163, 184, 0.2);
        box-shadow: inset 0 2px 0 rgba(37, 99, 235, 0.24);
    }
    .snapshot-tile:hover {
        transform: translateY(-4px);
        border-color: rgba(37, 99, 235, 0.3);
        box-shadow: 0 18px 28px rgba(15, 23, 42, 0.12);
    }
    .snapshot-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--dash-muted-2);
        margin-bottom: 6px;
    }
    .snapshot-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--dash-ink);
        word-break: break-word;
    }
    .snapshot-value.is-muted {
        color: var(--dash-muted-2);
        font-weight: 600;
    }
    .appointment-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }
    .appointment-kicker {
        font-size: 0.72rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--dash-muted-2);
        font-weight: 700;
    }
    .appointment-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 6px 0 4px;
        color: var(--dash-ink);
    }
    .appointment-sub {
        font-size: 0.85rem;
        color: var(--dash-muted);
        margin-bottom: 0;
    }
    .appointment-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        border: 1px solid transparent;
        background: rgba(37, 99, 235, 0.12);
        color: #1d4ed8;
    }
    .appointment-pill.tone-info { background: rgba(14, 165, 233, 0.15); color: #0284c7; border-color: rgba(14, 165, 233, 0.35); }
    .appointment-pill.tone-warning { background: rgba(245, 158, 11, 0.15); color: #d97706; border-color: rgba(245, 158, 11, 0.35); }
    .countdown-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 10px;
    }
    .countdown-slot {
        background: var(--dash-soft);
        border-radius: 14px;
        border: 1px solid rgba(37, 99, 235, 0.2);
        padding: 12px 10px;
        text-align: center;
    }
    .countdown-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--dash-ink);
        display: inline-block;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    .countdown-value.countdown-pulse {
        animation: countdownFlip 0.42s ease;
    }
    .countdown-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--dash-muted-2);
    }
    .countdown-meta {
        font-size: 0.85rem;
        color: var(--dash-muted);
        margin-bottom: 8px;
    }
    .empty-state {
        border: 1px dashed rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 16px;
        font-size: 0.85rem;
        color: var(--dash-muted);
        background: rgba(148, 163, 184, 0.08);
    }
    [data-reveal] {
        opacity: 0;
        transform: translateY(12px);
        transition: opacity 0.6s ease, transform 0.6s ease;
        transition-delay: var(--delay, 0s);
    }
    [data-reveal].is-visible {
        opacity: 1;
        transform: translateY(0);
    }
    @keyframes welcomeFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }
    @keyframes artFloat {
        0%, 100% { transform: translateY(0) rotate(-1deg); }
        50% { transform: translateY(-8px) rotate(1deg); }
    }
    @media (max-width: 991px) {
        .premium-hero {
            padding: 18px;
        }
        .welcome-strip {
            grid-template-columns: 1fr;
        }
        .welcome-art {
            min-height: clamp(180px, 40vw, 220px);
            justify-content: center;
        }
        .welcome-art-track {
            justify-content: center;
            padding: 8px 0;
        }
        .welcome-art-image {
            height: clamp(150px, 36vw, 200px);
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .premium-hero::after,
        .welcome-date::after,
        .welcome-art,
        [data-reveal] {
            animation: none !important;
            transition: none !important;
        }
        .countdown-value,
        .countdown-value.countdown-pulse {
            transition: none !important;
            animation: none !important;
        }
    }
</style>
<div class="container">

    <h1 class="mb-2">Dashboard</h1>
    <!-- <div class="dash-live-bar">
        <span class="live-pill">
            <span class="live-dot" id="dashboard_live_dot"></span>
            <span id="dashboard_live_text">Live</span>
        </span>
        <span class="dash-event" id="dashboard_event_line"></span>
    </div> -->

    @php
        $isStudent = auth()->user()->role === 'student';
    @endphp
    @if($isStudent)
        @php
            $user = auth()->user();
            $student = $user->student;
            $studentName = $user->name;
            $studentNumber = $student?->student_number ?? '—';
            $faculty = $student?->faculty ?? 'Not set';
            $program = $student?->department ?? 'Not set';
            $option = 'Not set';
            $campus = $student?->campus ?? 'Not set';
            $todayLabel = now()->format('l, F j, Y');
            $nextAppointment = $dashboardData['nextAppointment'] ?? [];
            $hasAppointment = !empty($nextAppointment);
        @endphp

        <div class="student-dashboard">
            <div class="premium-hero welcome-strip mb-5" data-reveal style="--delay: 0s;">
                <div class="welcome-content">
                    <div class="welcome-date">{{ $todayLabel }}</div>
                    <h2 class="welcome-title">Welcome back, {{ $studentName }}.</h2>
                    <p class="welcome-sub">
                      💡 Digital queues for a smarter future.
                    </p>
                    <div class="welcome-badges">
                        <!-- <span class="welcome-chip">Campus: {{ $campus }}</span>
                        <span class="welcome-chip">Student No: {{ $studentNumber }}</span> -->
                    </div>
                </div>
                <div class="welcome-art" id="welcome_strip_art">
                    <div class="welcome-art-track">
                        <img class="welcome-art-image" src="{{ asset('assets/images/hero-noqueue.png') }}" alt="Queue-free service" loading="lazy">
                    </div>
                    <!-- <div class="welcome-art-placeholder">
                        Add your hero artwork
                        <span>Share the image and I’ll wire it in.</span>
                    </div> -->
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="snapshot-card" data-reveal style="--delay: 0.1s;">
                        <div class="section-head">
                            <h4>Student Information</h4>
                            <!-- <a class="btn btn-sm btn-outline-primary" href="{{ route('profile.edit') }}">Edit profile</a> -->
                        </div>
                        <div class="snapshot-grid">
                            <div class="snapshot-tile">
                                <div class="snapshot-label">Campus</div>
                                <div class="snapshot-value">{{ $campus }}</div>
                            </div>
                            <div class="snapshot-tile">
                                <div class="snapshot-label">Faculty</div>
                                <div class="snapshot-value">{{ $faculty }}</div>
                            </div>
                            <div class="snapshot-tile">
                                <div class="snapshot-label">Department</div>
                                <div class="snapshot-value">{{ $program }}</div>
                            </div>
                            <!-- <div class="snapshot-tile">
                                <div class="snapshot-label">Option</div>
                                <div class="snapshot-value {{ $option === 'Not set' ? 'is-muted' : '' }}">{{ $option }}</div>
                            </div> -->
                            
                            <div class="snapshot-tile">
                                <div class="snapshot-label">Student No.</div>
                                <div class="snapshot-value">{{ $studentNumber }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="appointment-card" data-reveal style="--delay: 0.18s;">
                        <div class="appointment-head">
                            <div>
                                <div class="appointment-kicker">Appointment Countdown</div>
                                <div class="appointment-title">Upcoming Appointment</div>
                                <p class="appointment-sub">Stay ready with a live timer and calendar reminder.</p>
                            </div>
                            <span class="appointment-pill tone-{{ $hasAppointment ? 'info' : 'warning' }}" id="appointment_status_label">
                                {{ $hasAppointment ? 'Scheduled' : 'Awaiting' }}
                            </span>
                        </div>

                        <div id="appointment_empty" class="{{ $hasAppointment ? 'd-none' : '' }}">
                            <div class="empty-state">
                                No appointment yet. Once scheduled, a live countdown and calendar link will appear here.
                            </div>
                        </div>

                        <div id="appointment_countdown" class="{{ $hasAppointment ? '' : 'd-none' }}"
                             data-appointment-iso="{{ $nextAppointment['iso'] ?? '' }}"
                             data-appointment-title="{{ $nextAppointment['title'] ?? '' }}"
                             data-appointment-location="{{ $nextAppointment['location'] ?? '' }}"
                             data-appointment-url="{{ $nextAppointment['show_url'] ?? '' }}">
                            <div class="countdown-grid">
                                <div class="countdown-slot">
                                    <div class="countdown-value" id="appointment_days">--</div>
                                    <div class="countdown-label">Days</div>
                                </div>
                                <div class="countdown-slot">
                                    <div class="countdown-value" id="appointment_hours">--</div>
                                    <div class="countdown-label">Hours</div>
                                </div>
                                <div class="countdown-slot">
                                    <div class="countdown-value" id="appointment_minutes">--</div>
                                    <div class="countdown-label">Minutes</div>
                                </div>
                            </div>
                            <div class="countdown-meta" id="appointment_display">
                                {{ $nextAppointment['display'] ?? '' }}
                            </div>
                            <div class="countdown-meta" id="appointment_meta">
                                {{ trim(($nextAppointment['office_name'] ?? '') . ' · ' . ($nextAppointment['service_name'] ?? ''), ' ·') }}
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary btn-sm" id="appointment_add_to_calendar" href="#" role="button">Add to Calendar</a>
                                <a class="btn btn-primary btn-sm" id="appointment_view" href="{{ $nextAppointment['show_url'] ?? '#' }}">View details</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary shadow metric-card">
                    <div class="card-body">
                        <h5>Total Requests</h5>
                        <h3 class="metric-value" id="stat_total_requests">{{ $dashboardData['totalRequests'] }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card text-white bg-warning shadow metric-card">
                    <div class="card-body">
                        <h5>Pending Requests</h5>
                        <h3 class="metric-value" id="stat_pending_requests">{{ $dashboardData['pendingRequests'] }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card text-white bg-success shadow metric-card">
                    <div class="card-body">
                        <h5>Resolved Requests</h5>
                        <h3 class="metric-value" id="stat_resolved_requests">{{ $dashboardData['resolvedRequests'] }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card text-white bg-secondary shadow metric-card">
                    <div class="card-body">
                        <h5>Appointment Scheduled</h5>
                        <h3 class="metric-value" id="stat_appointment_scheduled">{{ $dashboardData['appointmentRequired'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        Requests by Office
                    </div>
                    <div class="card-body">
                        <canvas id="officeChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        Requests by Status
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>



<script>
    // Realtime dashboard stats/charts
    (function() {
        const liveStatsUrl = @json(route('dashboard.live-stats'));
        const realtimeOfficeIds = @json($dashboardData['realtimeOfficeIds']);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        let officeChart = null;
        let statusChart = null;
        let refreshPending = false;
        let hasRealtimeBound = false;
        let eventTimer = null;
        let idleQueueHintIndex = 0;
        let trackerAudioCtx = null;
        let previousTrackerToken = @json(data_get($dashboardData, 'studentQueueTracker.token_code'));
        let previousPeopleAhead = Number(@json(data_get($dashboardData, 'studentQueueTracker.people_ahead', 0)));
        const queueSoundStorageKey = 'uqs-student-queue-sound-enabled';
        let previousStats = {
            totalRequests: Number(@json($dashboardData['totalRequests'])),
            pendingRequests: Number(@json($dashboardData['pendingRequests'])),
            resolvedRequests: Number(@json($dashboardData['resolvedRequests'])),
            appointmentRequired: Number(@json($dashboardData['appointmentRequired'])),
        };
        const initialAppointment = @json($dashboardData['nextAppointment'] ?? null);
        let appointmentCountdownTimer = null;
        let currentAppointmentIso = null;
        let appointmentCalendarUrl = null;
        let countdownSnapshot = { days: null, hours: null, minutes: null };

        const els = {
            liveDot: document.getElementById('dashboard_live_dot'),
            liveText: document.getElementById('dashboard_live_text'),
            eventLine: document.getElementById('dashboard_event_line'),
            total: document.getElementById('stat_total_requests'),
            pending: document.getElementById('stat_pending_requests'),
            resolved: document.getElementById('stat_resolved_requests'),
            scheduled: document.getElementById('stat_appointment_scheduled'),
            queueEmpty: document.getElementById('student_queue_empty'),
            queueContent: document.getElementById('student_queue_content'),
            queueToken: document.getElementById('student_queue_token'),
            queueMode: document.getElementById('student_queue_mode'),
            queueStatus: document.getElementById('student_queue_status'),
            queueOffice: document.getElementById('student_queue_office'),
            queueLane: document.getElementById('student_queue_lane'),
            queuePosition: document.getElementById('student_queue_position'),
            queueAhead: document.getElementById('student_queue_ahead'),
            queueState: document.getElementById('student_queue_state'),
            queueLink: document.getElementById('student_queue_link'),
            queueProgress: document.getElementById('student_queue_progress'),
            queueSync: document.getElementById('student_queue_sync'),
            queueLiveLabel: document.getElementById('student_queue_live_label'),
            queueOfficeTitle: document.getElementById('student_queue_office_title'),
            queueTrackerLiveDot: document.getElementById('queue_tracker_live_dot'),
            queueTrackerLiveText: document.getElementById('queue_tracker_live_text'),
            queueSoundToggle: document.getElementById('student_queue_sound_toggle'),
            appointmentEmpty: document.getElementById('appointment_empty'),
            appointmentCountdown: document.getElementById('appointment_countdown'),
            appointmentDays: document.getElementById('appointment_days'),
            appointmentHours: document.getElementById('appointment_hours'),
            appointmentMinutes: document.getElementById('appointment_minutes'),
            appointmentDisplay: document.getElementById('appointment_display'),
            appointmentMeta: document.getElementById('appointment_meta'),
            appointmentAdd: document.getElementById('appointment_add_to_calendar'),
            appointmentView: document.getElementById('appointment_view'),
            appointmentStatusLabel: document.getElementById('appointment_status_label'),
        };

        const statusColors = {
            'Submitted': 'rgba(67, 94, 190, 0.7)',
            'In Review': 'rgba(255, 193, 7, 0.7)',
            'Awaiting Student Response': 'rgba(23, 162, 184, 0.7)',
            'Appointment Required': 'rgba(13, 110, 253, 0.7)',
            'Appointment Scheduled': 'rgba(153, 102, 255, 0.7)',
            'Resolved': 'rgba(40, 167, 69, 0.7)',
            'Closed': 'rgba(220, 53, 69, 0.7)',
            'Archived': 'rgba(108, 117, 125, 0.7)'
        };

        function statusBackgrounds(labels) {
            return labels.map(label => statusColors[label] || 'rgba(99, 99, 99, 0.7)');
        }

        function createCharts(data) {
            if (typeof window.Chart === 'undefined') {
                return false;
            }

            const officeCanvas = document.getElementById('officeChart');
            if (officeCanvas) {
                const deptCtx = officeCanvas.getContext('2d');
                officeChart = new Chart(deptCtx, {
                    type: 'bar',
                    data: {
                        labels: data.requestsPerOffice.labels,
                        datasets: [{
                            label: 'Number of Requests',
                            data: data.requestsPerOffice.counts,
                            backgroundColor: 'rgba(67, 94, 190, 0.7)',
                            borderColor: 'rgba(67, 94, 190, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        animation: {
                            duration: prefersReducedMotion ? 0 : 450
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            },
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                });
            }

            const statusCanvas = document.getElementById('statusChart');
            if (statusCanvas) {
                const statusCtx = statusCanvas.getContext('2d');
                statusChart = new Chart(statusCtx, {
                    type: 'pie',
                    data: {
                        labels: data.requestsPerStatus.labels,
                        datasets: [{
                            data: data.requestsPerStatus.counts,
                            backgroundColor: statusBackgrounds(data.requestsPerStatus.labels),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        animation: {
                            duration: prefersReducedMotion ? 0 : 450
                        }
                    }
                });
            }

            return true;
        }

        function setLiveState(isOnline) {
            if (!els.liveDot || !els.liveText) return;
            if (isOnline) {
                els.liveDot.classList.remove('reconnecting');
                els.liveText.textContent = 'Live';
                if (els.queueTrackerLiveDot) els.queueTrackerLiveDot.classList.remove('reconnecting');
                if (els.queueTrackerLiveText) els.queueTrackerLiveText.textContent = 'Live';
            } else {
                els.liveDot.classList.add('reconnecting');
                els.liveText.textContent = 'Reconnecting...';
                if (els.queueTrackerLiveDot) els.queueTrackerLiveDot.classList.add('reconnecting');
                if (els.queueTrackerLiveText) els.queueTrackerLiveText.textContent = 'Reconnecting...';
            }
        }

        function showEvent(message) {
            if (!els.eventLine || !message) return;
            els.eventLine.textContent = message;
            els.eventLine.classList.add('visible');
            clearTimeout(eventTimer);
            eventTimer = setTimeout(() => {
                els.eventLine.classList.remove('visible');
            }, 2200);
        }

        function bump(el) {
            if (!el || prefersReducedMotion) return;
            el.classList.remove('bump');
            requestAnimationFrame(() => {
                el.classList.add('bump');
                setTimeout(() => el.classList.remove('bump'), 260);
            });
        }

        function trackerSoundEnabled() {
            return !!els.queueSoundToggle?.checked;
        }

        function playTrackerCue() {
            if (!trackerSoundEnabled()) return;
            try {
                trackerAudioCtx = trackerAudioCtx || new (window.AudioContext || window.webkitAudioContext)();
                if (trackerAudioCtx.state === 'suspended') {
                    trackerAudioCtx.resume().catch(() => {});
                }
                const now = trackerAudioCtx.currentTime;
                const gain = trackerAudioCtx.createGain();
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.035, now + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.2);
                gain.connect(trackerAudioCtx.destination);

                const osc = trackerAudioCtx.createOscillator();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, now);
                osc.connect(gain);
                osc.start(now);
                osc.stop(now + 0.2);
            } catch (_) {
                // Ignore audio failures.
            }
        }

        function speakTracker(message) {
            if (!trackerSoundEnabled() || !window.speechSynthesis || !message) return;
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.rate = 0.98;
            utterance.pitch = 1;
            utterance.volume = 1;
            window.speechSynthesis.speak(utterance);
        }

        function animateNumber(el, from, to) {
            if (!el) return;
            if (prefersReducedMotion) {
                el.textContent = to;
                return;
            }
            const duration = 360;
            const start = performance.now();
            function frame(now) {
                const progress = Math.min((now - start) / duration, 1);
                const value = Math.round(from + (to - from) * progress);
                el.textContent = value;
                if (progress < 1) requestAnimationFrame(frame);
            }
            requestAnimationFrame(frame);
        }

        const appointmentToneClasses = ['tone-info', 'tone-warning'];

        function applyAppointmentTone(tone) {
            if (!els.appointmentStatusLabel) return;
            appointmentToneClasses.forEach(cls => els.appointmentStatusLabel.classList.remove(cls));
            els.appointmentStatusLabel.classList.add(`tone-${tone}`);
        }

        function setCountdownValue(el, key, value) {
            if (!el) return;
            if (countdownSnapshot[key] === value) return;
            countdownSnapshot[key] = value;
            el.textContent = value;
            if (!prefersReducedMotion) {
                el.classList.remove('countdown-pulse');
                requestAnimationFrame(() => {
                    el.classList.add('countdown-pulse');
                    setTimeout(() => el.classList.remove('countdown-pulse'), 220);
                });
            }
        }

        function formatCalendarStamp(date) {
            return date.toISOString().replace(/[-:]/g, '').replace(/\\.\\d{3}Z$/, 'Z');
        }

        function buildCalendarFile(appointment) {
            if (!appointment?.iso) return null;
            const start = new Date(appointment.iso);
            const end = new Date(start.getTime() + 30 * 60 * 1000);
            const uid = `uqs-${start.getTime()}-university-queue`;
            const summary = appointment.title || 'Appointment';
            const location = appointment.location || '';
            const description = appointment.office_name ? `Office: ${appointment.office_name}` : '';

            const lines = [
                'BEGIN:VCALENDAR',
                'VERSION:2.0',
                'PRODID:-//University Queue//EN',
                'BEGIN:VEVENT',
                `UID:${uid}`,
                `DTSTAMP:${formatCalendarStamp(new Date())}`,
                `DTSTART:${formatCalendarStamp(start)}`,
                `DTEND:${formatCalendarStamp(end)}`,
                `SUMMARY:${summary.replace(/\\n/g, ' ')}`,
                `LOCATION:${location.replace(/\\n/g, ' ')}`,
                `DESCRIPTION:${description.replace(/\\n/g, ' ')}`,
                'END:VEVENT',
                'END:VCALENDAR'
            ];
            return new Blob([lines.join('\\r\\n')], { type: 'text/calendar' });
        }

        function updateAppointmentCountdown(nextAppointment) {
            if (!els.appointmentEmpty || !els.appointmentCountdown) return;
            if (!nextAppointment || !nextAppointment.iso) {
                els.appointmentEmpty.classList.remove('d-none');
                els.appointmentCountdown.classList.add('d-none');
                if (els.appointmentStatusLabel) {
                    els.appointmentStatusLabel.textContent = 'Awaiting';
                    applyAppointmentTone('warning');
                }
                if (els.appointmentMeta) {
                    els.appointmentMeta.textContent = '';
                }
                currentAppointmentIso = null;
                if (appointmentCalendarUrl) {
                    URL.revokeObjectURL(appointmentCalendarUrl);
                    appointmentCalendarUrl = null;
                }
                if (els.appointmentAdd) {
                    els.appointmentAdd.removeAttribute('href');
                    els.appointmentAdd.removeAttribute('download');
                }
                if (appointmentCountdownTimer) {
                    clearInterval(appointmentCountdownTimer);
                    appointmentCountdownTimer = null;
                }
                return;
            }

            els.appointmentEmpty.classList.add('d-none');
            els.appointmentCountdown.classList.remove('d-none');
            if (els.appointmentStatusLabel) {
                els.appointmentStatusLabel.textContent = 'Scheduled';
                applyAppointmentTone('info');
            }

            if (currentAppointmentIso !== nextAppointment.iso) {
                currentAppointmentIso = nextAppointment.iso;
                if (els.appointmentCountdown) {
                    els.appointmentCountdown.dataset.appointmentIso = nextAppointment.iso;
                    els.appointmentCountdown.dataset.appointmentTitle = nextAppointment.title || '';
                    els.appointmentCountdown.dataset.appointmentLocation = nextAppointment.location || '';
                    els.appointmentCountdown.dataset.appointmentUrl = nextAppointment.show_url || '';
                }
                if (els.appointmentDisplay) {
                    els.appointmentDisplay.textContent = nextAppointment.display || '';
                }
                if (els.appointmentMeta) {
                    const meta = [nextAppointment.office_name, nextAppointment.service_name].filter(Boolean).join(' · ');
                    els.appointmentMeta.textContent = meta;
                }
                if (els.appointmentView && nextAppointment.show_url) {
                    els.appointmentView.setAttribute('href', nextAppointment.show_url);
                }
                if (els.appointmentAdd) {
                    if (appointmentCalendarUrl) {
                        URL.revokeObjectURL(appointmentCalendarUrl);
                        appointmentCalendarUrl = null;
                    }
                    const file = buildCalendarFile(nextAppointment);
                    if (file) {
                        appointmentCalendarUrl = URL.createObjectURL(file);
                        els.appointmentAdd.setAttribute('href', appointmentCalendarUrl);
                        els.appointmentAdd.setAttribute('download', 'appointment.ics');
                    }
                }
            }

            const updateCountdown = () => {
                if (!currentAppointmentIso) return;
                const target = new Date(currentAppointmentIso).getTime();
                const now = Date.now();
                const diff = Math.max(0, target - now);
                const totalMinutes = Math.floor(diff / 60000);
                const days = Math.floor(totalMinutes / 1440);
                const hours = Math.floor((totalMinutes % 1440) / 60);
                const minutes = totalMinutes % 60;

                setCountdownValue(els.appointmentDays, 'days', String(days));
                setCountdownValue(els.appointmentHours, 'hours', String(hours).padStart(2, '0'));
                setCountdownValue(els.appointmentMinutes, 'minutes', String(minutes).padStart(2, '0'));
            };

            updateCountdown();
            if (!appointmentCountdownTimer) {
                appointmentCountdownTimer = setInterval(updateCountdown, 1000);
            }
        }

        function revealDashboard() {
            document.querySelectorAll('[data-reveal]').forEach((el) => {
                el.classList.add('is-visible');
            });
        }

        function initWelcomeArt() {
            const art = document.getElementById('welcome_strip_art');
            if (!art) return;
            const images = art.querySelectorAll('img');
            if (!images.length) return;
            const markReady = () => art.classList.add('has-images');
            images.forEach((img) => {
                if (img.complete && img.naturalWidth > 0) {
                    markReady();
                } else {
                    img.addEventListener('load', markReady, { once: true });
                }
                img.addEventListener('error', () => {
                    img.classList.add('d-none');
                }, { once: true });
            });
        }

        function updateStatsDom(data) {
            const next = {
                totalRequests: Number(data.totalRequests),
                pendingRequests: Number(data.pendingRequests),
                resolvedRequests: Number(data.resolvedRequests),
                appointmentRequired: Number(data.appointmentRequired),
            };

            if (next.totalRequests !== previousStats.totalRequests) {
                animateNumber(els.total, previousStats.totalRequests, next.totalRequests);
                bump(els.total);
            }
            if (next.pendingRequests !== previousStats.pendingRequests) {
                animateNumber(els.pending, previousStats.pendingRequests, next.pendingRequests);
                bump(els.pending);
            }
            if (next.resolvedRequests !== previousStats.resolvedRequests) {
                animateNumber(els.resolved, previousStats.resolvedRequests, next.resolvedRequests);
                bump(els.resolved);
            }
            if (next.appointmentRequired !== previousStats.appointmentRequired) {
                animateNumber(els.scheduled, previousStats.appointmentRequired, next.appointmentRequired);
                bump(els.scheduled);
            }

            if (next.pendingRequests < previousStats.pendingRequests) {
                showEvent('Queue progressed: pending requests reduced.');
            } else if (next.pendingRequests > previousStats.pendingRequests) {
                showEvent('New requests entered the queue.');
            } else if (next.resolvedRequests > previousStats.resolvedRequests) {
                showEvent('More requests resolved.');
            }

            previousStats = next;
        }

        function updateStudentQueueTracker(data) {
            if (!els.queueEmpty || !els.queueContent) return;
            const idleHints = [
                'No active queue yet',
                'Waiting for QR scan join',
                'Queue monitor is standing by',
            ];

            const tracker = data.studentQueueTracker || null;
            if (!tracker) {
                els.queueEmpty.classList.remove('d-none');
                els.queueContent.classList.add('d-none');
                if (els.queueOfficeTitle) els.queueOfficeTitle.textContent = 'My Queue Tracker';
                if (els.queueSync) {
                    els.queueSync.textContent = idleHints[idleQueueHintIndex % idleHints.length];
                    idleQueueHintIndex += 1;
                }
                if (els.queueLiveLabel) els.queueLiveLabel.textContent = 'Live Queue Monitor';
                previousTrackerToken = null;
                previousPeopleAhead = 0;
                return;
            }

            els.queueEmpty.classList.add('d-none');
            els.queueContent.classList.remove('d-none');
            if (els.queueOfficeTitle) els.queueOfficeTitle.textContent = tracker.office_name || 'My Queue Tracker';
            if (els.queueToken) els.queueToken.textContent = tracker.token_code || 'N/A';
            if (els.queueMode) els.queueMode.textContent = tracker.request_mode || 'WALK_IN';
            if (els.queueStatus) els.queueStatus.textContent = tracker.status || 'Submitted';
            if (els.queueOffice) els.queueOffice.textContent = tracker.office_name || 'Office';
            if (els.queueLane) els.queueLane.textContent = tracker.lane_label || 'General Queue';
            if (els.queuePosition) els.queuePosition.textContent = tracker.queue_position ?? '-';
            if (els.queueAhead) els.queueAhead.textContent = tracker.people_ahead ?? '-';
            if (els.queueState) els.queueState.textContent = tracker.queue_state || '';
            if (els.queueSync) els.queueSync.textContent = `Updated at ${tracker.updated_at || '--:--:--'}`;
            if (els.queueLiveLabel) els.queueLiveLabel.textContent = Number(tracker.people_ahead || 0) === 0
                ? 'You are next in line'
                : 'Live Queue Monitor';
            if (els.queueProgress) {
                const ahead = Number(tracker.people_ahead || 0);
                const progress = Math.max(5, Math.min(100, Math.round(100 / (ahead + 1))));
                els.queueProgress.style.width = `${progress}%`;
            }
            if (els.queueLink && tracker.show_url) {
                els.queueLink.classList.remove('disabled');
                els.queueLink.setAttribute('href', tracker.show_url);
            }

            if (previousTrackerToken && previousTrackerToken !== tracker.token_code) {
                showEvent(`Token changed: now ${tracker.token_code}`);
                playTrackerCue();
                speakTracker(`Token ${tracker.token_code}. ${tracker.queue_state || 'Queue updated'}`);
                if (els.queueToken) bump(els.queueToken);
            } else if (previousPeopleAhead !== Number(tracker.people_ahead || 0)) {
                showEvent(`People ahead: ${tracker.people_ahead}`);
                if (Number(tracker.people_ahead || 0) === 0) {
                    playTrackerCue();
                    speakTracker(`You are next. Token ${tracker.token_code}`);
                }
            }
            previousTrackerToken = tracker.token_code || null;
            previousPeopleAhead = Number(tracker.people_ahead || 0);
        }

        function updateCharts(data) {
            if (officeChart) {
                officeChart.data.labels = data.requestsPerOffice.labels;
                officeChart.data.datasets[0].data = data.requestsPerOffice.counts;
                officeChart.update('active');
            }

            if (statusChart) {
                statusChart.data.labels = data.requestsPerStatus.labels;
                statusChart.data.datasets[0].data = data.requestsPerStatus.counts;
                statusChart.data.datasets[0].backgroundColor = statusBackgrounds(data.requestsPerStatus.labels);
                statusChart.update('active');
            }
        }

        async function refreshDashboard() {
            if (refreshPending) return;
            refreshPending = true;
            try {
                const response = await fetch(liveStatsUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                    }
                });
                if (!response.ok) throw new Error('Failed to load dashboard stats');
                const data = await response.json();
                setLiveState(true);
                updateStatsDom(data);
                updateStudentQueueTracker(data);
                updateAppointmentCountdown(data.nextAppointment);
                updateCharts(data);
            } catch (_) {
                // Keep existing data on temporary failures.
                setLiveState(false);
            } finally {
                refreshPending = false;
            }
        }

        function bindRealtime() {
            if (hasRealtimeBound || !window.UQSEcho || !Array.isArray(realtimeOfficeIds) || realtimeOfficeIds.length === 0) {
                return;
            }

            hasRealtimeBound = true;
            realtimeOfficeIds.forEach((officeId) => {
                window.UQSEcho.channel(`office.${officeId}`)
                    .listen('.queue.lane.updated', () => {
                        setLiveState(true);
                        showEvent('Live update received from office queue.');
                        refreshDashboard();
                    });
            });
        }

        function bootCharts() {
            let attempts = 0;
            const maxAttempts = 20;
            const timer = setInterval(function() {
                attempts += 1;
                if (createCharts(@json($dashboardData)) || attempts >= maxAttempts) {
                    clearInterval(timer);
                }
            }, 150);
        }

        function initStudentDashboard() {
            updateAppointmentCountdown(initialAppointment);
            initWelcomeArt();
            revealDashboard();
        }

        if (document.readyState === 'complete') {
            bootCharts();
            bindRealtime();
            initStudentDashboard();
            if (els.queueSoundToggle) {
                const saved = localStorage.getItem(queueSoundStorageKey);
                els.queueSoundToggle.checked = saved === null ? true : saved === '1';
                els.queueSoundToggle.addEventListener('change', () => {
                    localStorage.setItem(queueSoundStorageKey, els.queueSoundToggle.checked ? '1' : '0');
                    if (els.queueSoundToggle.checked) {
                        playTrackerCue();
                    } else if (window.speechSynthesis) {
                        window.speechSynthesis.cancel();
                    }
                });
            }
        } else {
            window.addEventListener('load', function () {
                bootCharts();
                bindRealtime();
                initStudentDashboard();
                if (els.queueSoundToggle) {
                    const saved = localStorage.getItem(queueSoundStorageKey);
                    els.queueSoundToggle.checked = saved === null ? true : saved === '1';
                    els.queueSoundToggle.addEventListener('change', () => {
                        localStorage.setItem(queueSoundStorageKey, els.queueSoundToggle.checked ? '1' : '0');
                        if (els.queueSoundToggle.checked) {
                            playTrackerCue();
                        } else if (window.speechSynthesis) {
                            window.speechSynthesis.cancel();
                        }
                    });
                }
            });
        }

        document.addEventListener('uqs:echo-ready', bindRealtime);
        setInterval(() => {
            if (els.queueEmpty && !els.queueEmpty.classList.contains('d-none') && els.queueSync) {
                const hints = [
                    'No active queue yet',
                    'Waiting for QR scan join',
                    'Queue monitor is standing by',
                ];
                els.queueSync.textContent = hints[idleQueueHintIndex % hints.length];
                idleQueueHintIndex += 1;
            }
        }, 5000);
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                refreshDashboard();
            }
        }, 30000);
    })();
</script>
@endsection
