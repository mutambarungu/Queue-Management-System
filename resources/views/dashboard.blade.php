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
</style>
<div class="container">

    <h1 class="mb-2">Dashboard</h1>
    <div class="dash-live-bar">
        <span class="live-pill">
            <span class="live-dot" id="dashboard_live_dot"></span>
            <span id="dashboard_live_text">Live</span>
        </span>
        <span class="dash-event" id="dashboard_event_line"></span>
    </div>

    @php($isStudent = auth()->user()->role === 'student')
    @if($isStudent)
        <div class="row g-3">
            <div class="col-lg-12">
                <div class="row mb-1">
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-primary shadow metric-card">
                            <div class="card-body">
                                <h5>Total Requests</h5>
                                <h3 class="metric-value" id="stat_total_requests">{{ $dashboardData['totalRequests'] }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-warning shadow metric-card">
                            <div class="card-body">
                                <h5>Pending Requests</h5>
                                <h3 class="metric-value" id="stat_pending_requests">{{ $dashboardData['pendingRequests'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-success shadow metric-card">
                            <div class="card-body">
                                <h5>Resolved Requests</h5>
                                <h3 class="metric-value" id="stat_resolved_requests">{{ $dashboardData['resolvedRequests'] }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-secondary shadow metric-card">
                            <div class="card-body">
                                <h5>Appointment Scheduled</h5>
                                <h3 class="metric-value" id="stat_appointment_scheduled">{{ $dashboardData['appointmentRequired'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card shadow mb-3">
                    <div class="card-header">Requests by Office</div>
                    <div class="card-body"><canvas id="officeChart"></canvas></div>
                </div>
                <div class="card shadow mb-3">
                    <div class="card-header">Requests by Status</div>
                    <div class="card-body"><canvas id="statusChart"></canvas></div>
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
        let previousTrackerToken = @json($dashboardData['studentQueueTracker']['token_code'] ?? null);
        let previousPeopleAhead = Number(@json($dashboardData['studentQueueTracker']['people_ahead'] ?? 0));
        const queueSoundStorageKey = 'uqs-student-queue-sound-enabled';
        let previousStats = {
            totalRequests: Number(@json($dashboardData['totalRequests'])),
            pendingRequests: Number(@json($dashboardData['pendingRequests'])),
            resolvedRequests: Number(@json($dashboardData['resolvedRequests'])),
            appointmentRequired: Number(@json($dashboardData['appointmentRequired'])),
        };

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

        if (document.readyState === 'complete') {
            bootCharts();
            bindRealtime();
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
