@extends('layouts.app')
@section('title', 'Request Detail: ' . $request->request_number)
@section('content')
<style>
    .live-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(25, 135, 84, 0.12);
        color: #198754;
        font-weight: 600;
        border: 1px solid rgba(25, 135, 84, 0.35);
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

    .metric-transition {
        transition: transform 0.24s ease, filter 0.24s ease;
    }

    .metric-transition.bump {
        transform: scale(1.1);
        filter: brightness(1.16);
    }

    .queue-card-live {
        position: relative;
        overflow: hidden;
    }

    .queue-card-live::after {
        content: "";
        position: absolute;
        inset: -80% -40%;
        background: radial-gradient(circle, rgba(13, 110, 253, 0.18), transparent 55%);
        pointer-events: none;
        animation: cardGlow 3.2s linear infinite;
    }

    .transient-event {
        opacity: 0;
        transform: translateY(-6px);
        transition: all 0.25s ease;
    }

    .transient-event.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .queue-stage-track {
        display: flex;
        align-items: stretch;
        gap: 0;
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(13, 110, 253, 0.18);
        background: #fff;
        --stage-progress: 0%;
    }

    .queue-stage-track::before {
        content: "";
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: var(--stage-progress);
        background: linear-gradient(90deg, #0d6efd, #20c997);
        transition: width .45s ease;
    }

    .queue-stage {
        position: relative;
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 8px;
        font-weight: 600;
        font-size: 0.86rem;
        color: #6c757d;
        transition: background-color .35s ease, color .35s ease;
    }

    .queue-stage::after {
        content: "";
        position: absolute;
        right: 0;
        top: 18%;
        width: 1px;
        height: 64%;
        background: rgba(13, 110, 253, 0.16);
    }

    .queue-stage:last-child::after {
        display: none;
    }

    .queue-stage .stage-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #adb5bd;
        transition: transform .3s ease, background-color .35s ease, box-shadow .35s ease;
    }

    .queue-stage .stage-icon {
        font-size: 0.95rem;
        transition: transform .26s ease, opacity .26s ease, color .35s ease;
        opacity: .72;
    }

    .queue-stage.active {
        background: rgba(13, 110, 253, 0.12);
        color: #0d6efd;
    }

    .queue-stage.active .stage-dot {
        background: #0d6efd;
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.35);
    }

    .queue-stage.active .stage-icon {
        opacity: 1;
        color: #0d6efd;
    }

    .queue-stage.current {
        background: rgba(13, 110, 253, 0.2);
        color: #0b5ed7;
    }

    .queue-stage.current .stage-dot {
        transform: scale(1.1);
        animation: stagePulse 1.4s infinite;
    }

    .queue-stage.current .stage-icon {
        animation: iconPulse 1.4s infinite;
    }

    body.dark-mode .queue-stage-track {
        background: #0f1729;
        border-color: rgba(74, 144, 255, 0.35);
    }

    body.dark-mode .queue-stage {
        background: transparent;
        color: #9fb1c9 !important;
    }

    body.dark-mode .queue-stage .stage-label {
        color: inherit !important;
    }

    body.dark-mode .queue-stage::after {
        background: rgba(159, 177, 201, 0.22);
    }

    body.dark-mode .queue-stage.active {
        background: rgba(74, 144, 255, 0.16);
        color: #c6dcff !important;
    }

    body.dark-mode .queue-stage.current {
        background: rgba(74, 144, 255, 0.28);
        color: #ffffff !important;
    }

    body.dark-mode .queue-stage.active .stage-dot {
        background: #79afff;
    }

    body.dark-mode .queue-stage.current .stage-dot {
        background: #a8ccff;
    }

    body.dark-mode .queue-stage.active .stage-icon,
    body.dark-mode .queue-stage.current .stage-icon {
        color: currentColor;
    }

    .queue-stage.stage-flash {
        animation: stageFlash 0.5s ease;
    }

    .queue-stage .stage-icon.morphing {
        transform: scale(0.85) rotate(-12deg);
        opacity: .4;
    }

    @keyframes livePulse {
        70% { box-shadow: 0 0 0 12px rgba(25, 135, 84, 0); }
        100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
    }

    @keyframes cardGlow {
        0% { transform: translateX(-10%); }
        100% { transform: translateX(10%); }
    }

    @keyframes stagePulse {
        70% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
        100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
    }

    @keyframes stageFlash {
        0% { filter: brightness(1); }
        50% { filter: brightness(1.4); }
        100% { filter: brightness(1); }
    }

    @keyframes iconPulse {
        50% { transform: scale(1.12); }
    }
</style>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">
                    <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                    Request Detail: {{ $request->request_number }}
                </h3>
                <a href="{{ route('student.requests.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
            <!-- Live Queue Card -->
            @php
                $isQueueActive = in_array($request->status, ['Submitted', 'In Review', 'Awaiting Student Response'], true);
            @endphp

            @if($isQueueActive)
            <div class="card mb-4 shadow-sm rounded-4 queue-card-live">
                <div class="card-header bg-primary bg-opacity-10">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 fw-semibold">
                            <i class="bi bi-hourglass-split me-2"></i> Live Queue Status
                        </h5>
                        <label class="form-check form-switch mb-0 ps-2">
                            <input class="form-check-input" type="checkbox" id="queue_sound_toggle">
                            <span class="small text-muted">Sound</span>
                        </label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <span class="badge bg-dark" id="lane_label">
                            Lane: {{ optional(optional($request->serviceType)->subOffice)->name ?: 'General Queue' }}
                        </span>
                        <span class="live-pill" id="live_status_badge">
                            <span class="live-dot" id="live_dot"></span>
                            <span id="live_status_text">Live</span>
                        </span>
                    </div>
                    <div class="small text-primary-emphasis mb-2 transient-event" id="queue_event"></div>

                    <div class="alert alert-info py-2 mb-3" id="queue_state_line">
                        {{ $request->queue_state }}
                    </div>

                    <div class="row text-center align-items-center">
                        <div class="col-md-2 mb-3">
                            <h6>Queue Position</h6>
                            <span class="badge bg-primary fs-5 metric-transition" id="queue_position">#{{ $request->queue_position }}</span>
                        </div>
                        <div class="col-md-2 mb-3">
                            <h6>People Ahead</h6>
                            <span class="badge bg-warning fs-5 metric-transition" id="people_ahead">{{ $request->people_ahead }}</span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6>Currently Serving</h6>
                            <span class="badge bg-info fs-6 metric-transition" id="currently_serving">
                                {{ optional($request->currently_serving)->queue_position ?? 'None' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-2 mb-3">
                        @php
                            $stages = ['Submitted', 'In Review', 'Resolved'];
                            $activeStageIndex = match ($request->status) {
                                'Submitted' => 0,
                                'In Review', 'Awaiting Student Response', 'Appointment Required', 'Appointment Scheduled' => 1,
                                'Resolved', 'Closed' => 2,
                                default => 0,
                            };
                        @endphp
                        <div class="queue-stage-track" id="status_stages" style="--stage-progress: {{ (int) (($activeStageIndex / 2) * 100) }}%;">
                            @foreach($stages as $index => $stage)
                                <div
                                    class="queue-stage {{ $index <= $activeStageIndex ? 'active' : '' }} {{ $index === $activeStageIndex ? 'current' : '' }}"
                                    data-stage-index="{{ $index }}">
                                    <em class="bi {{ $index === 0 ? 'bi-send-fill' : ($index === 1 ? 'bi-activity' : 'bi-flag-fill') }} stage-icon"></em>
                                    <span class="stage-dot"></span>
                                    <span class="stage-label">{{ $stage }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-3">
                        @php
                            $totalQueue = max($request->queue_position + $request->people_ahead, 1);
                            $progress = round(($totalQueue - $request->queue_position) / $totalQueue * 100);
                        @endphp
                        <div class="progress rounded-pill" style="height: 20px;">
                            <div class="progress-bar bg-primary" id="queue_progress_bar" role="progressbar" style="width: {{ $progress }}%; transition: width 0.6s ease;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                {{ $progress }}%
                            </div>
                        </div>
                        <small class="text-muted">Progress towards your turn</small>
                    </div>
                </div>
            </div>
            @else
            <div class="card mb-4 shadow-sm rounded-4">
                <div class="card-header bg-success bg-opacity-10">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bi bi-check2-circle me-2"></i> Request Status
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        This request is no longer in the live queue.
                    </p>
                    <span class="badge bg-success fs-6">{{ $request->status }}</span>
                    <p class="text-muted mt-2 mb-0">
                        Last updated: {{ $request->updated_at?->format('d M Y h:i A') }}
                    </p>
                </div>
            </div>
            @endif

            <!-- Student Info Card -->
            @php
                $student = $request->student;
                $user = optional($student)->user;
                $studentName = $user->name ?? 'N/A';
                $avatarUrl = $student->avatar
                    ?? 'https://ui-avatars.com/api/?name=' . urlencode($studentName !== 'N/A' ? $studentName : 'Student') . '&background=0D6EFD&color=fff';
            @endphp
            <div class="card mb-4 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="row g-3 align-items-start">
                        <div class="col-12 col-md-auto text-center">
                            <img src="{{ $avatarUrl }}"
                                class="rounded-circle border shadow-sm"
                                style="width: 84px; height: 84px; object-fit: cover;"
                                alt="Student Avatar">
                        </div>
                        <div class="col">
                            <h5 class="mb-1">{{ $studentName }}</h5>
                            <p class="mb-3 text-muted text-break">{{ $user->email ?? 'N/A' }}</p>

                            <div class="row row-cols-1 row-cols-sm-2 g-2">
                                <div class="col">
                                    <div class="rounded-3 p-2 h-100">
                                        <small class="text-muted d-block">Student Number</small>
                                        <span class="fw-semibold">{{ $student->student_number ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="rounded-3 p-2 h-100">
                                        <small class="text-muted d-block">Phone</small>
                                        <span class="fw-semibold text-break">{{ $student->phone ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="rounded-3 p-2 h-100">
                                        <small class="text-muted d-block">Office</small>
                                        <span class="fw-semibold">{{ $request->office->name ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="rounded-3 p-2 h-100">
                                        <small class="text-muted d-block">Department</small>
                                        <span class="fw-semibold">{{ $student->department ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Details Card -->
            @php
                $statusClass = match ($request->status) {
                    'Submitted' => 'primary',
                    'In Review' => 'warning',
                    'Awaiting Student Response' => 'info',
                    'Appointment Required' => 'secondary',
                    'Resolved' => 'success',
                    'Closed' => 'dark',
                    default => 'secondary',
                };
            @endphp

            <div class="card mb-4 shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="fw-semibold">Request Details</h5>
                    <p><strong>Service Type:</strong> {{ $request->serviceType->name ?? 'N/A' }}</p>
                    <p><strong>Description:</strong> {{ $request->description }}</p>
                    @if($request->attachments->count())
                        <p><strong>Attachments:</strong></p>
                        <ul>
                            @foreach($request->attachments as $att)
                                <li>
                                    <a href="{{ route('attachments.request', $att) }}" target="_blank" rel="noopener">
                                        {{ $att->file_name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <p>
                        <strong>Status:</strong>
                        <span class="badge bg-{{ $statusClass }}">{{ $request->status }}</span>
                    </p>
                </div>
            </div>

            <!-- Replies Card -->
            <div class="card mb-4 shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Replies</h5>
                    @forelse($request->replies as $reply)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">{{ $reply->user->name }} | {{ $reply->created_at->format('d M Y h:i A') }}</small>
                            </div>
                            <p class="mb-1">{{ $reply->message }}</p>
                            @if($reply->attachment)
                                <p class="mb-0"><strong>Attachment:</strong>
                                    <a href="{{ route('attachments.reply', $reply) }}" target="_blank" rel="noopener">View</a>
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">No replies yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="card mb-4 shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Status Timeline</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-primary me-2"></i>
                            Submitted – {{ $request->created_at->format('d M Y h:i A') }}
                        </li>
                        @foreach($request->replies as $reply)
                            <li class="list-group-item">
                                <i class="bi bi-chat-left-text text-info me-2"></i>
                                {{ $reply->user->name }} replied – {{ $reply->created_at->format('d M Y h:i A') }}
                            </li>
                        @endforeach
                        @if($request->appointment)
                            <li class="list-group-item">
                                <i class="bi bi-calendar-event text-success me-2"></i>
                                Appointment Scheduled: {{ $request->appointment->appointment_date }} at {{ $request->appointment->appointment_time }}
                            </li>
                        @endif
                        @if($request->status === 'Resolved')
                            <li class="list-group-item">
                                <i class="bi bi-flag-fill text-success me-2"></i>
                                Request Resolved
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- AJAX Live Queue Update -->
@if($isQueueActive)
<script>
    const els = {
        queuePosition: document.getElementById('queue_position'),
        peopleAhead: document.getElementById('people_ahead'),
        currentlyServing: document.getElementById('currently_serving'),
        stateLine: document.getElementById('queue_state_line'),
        laneLabel: document.getElementById('lane_label'),
        statusStages: document.getElementById('status_stages'),
        progressBar: document.getElementById('queue_progress_bar'),
        liveDot: document.getElementById('live_dot'),
        liveText: document.getElementById('live_status_text'),
        eventLine: document.getElementById('queue_event'),
        soundToggle: document.getElementById('queue_sound_toggle'),
    };

    let previous = {
        queue_position: {{ (int) $request->queue_position }},
        people_ahead: {{ (int) $request->people_ahead }},
        currently_serving: {{ optional($request->currently_serving)->queue_position ? (int) optional($request->currently_serving)->queue_position : 'null' }},
        status: @json($request->status),
    };
    let pending = false;
    let audioCtx = null;
    const SOUND_KEY = 'uqs-queue-sound-enabled';
    const requestId = {{ (int) $request->id }};
    const defaultOfficeId = {{ (int) $request->office_id }};
    let activeOfficeId = defaultOfficeId;
    let realtimeBound = false;

    async function unlockAudio() {
        if (!els.soundToggle?.checked) return;
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') {
                await audioCtx.resume();
            }
        } catch (_) {
            // Ignore unlock errors
        }
    }

    function playSoftCue() {
        if (!els.soundToggle?.checked) return;
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') {
                audioCtx.resume().catch(() => {});
            }
            const now = audioCtx.currentTime;
            const gain = audioCtx.createGain();
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.04, now + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.24);
            gain.connect(audioCtx.destination);

            const osc1 = audioCtx.createOscillator();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(740, now);
            osc1.connect(gain);
            osc1.start(now);
            osc1.stop(now + 0.12);

            const osc2 = audioCtx.createOscillator();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(988, now + 0.11);
            osc2.connect(gain);
            osc2.start(now + 0.11);
            osc2.stop(now + 0.24);
        } catch (_) {
            // Ignore audio errors
        }
    }

    function bump(el) {
        if (!el) return;
        el.classList.remove('bump');
        requestAnimationFrame(() => {
            el.classList.add('bump');
            setTimeout(() => el.classList.remove('bump'), 260);
        });
    }

    function showEvent(message) {
        if (!message) return;
        els.eventLine.textContent = message;
        els.eventLine.classList.add('visible');
        clearTimeout(showEvent._timer);
        showEvent._timer = setTimeout(() => els.eventLine.classList.remove('visible'), 2200);
    }

    function setLiveState(isOnline) {
        if (isOnline) {
            els.liveDot.classList.remove('reconnecting');
            els.liveText.textContent = 'Live';
        } else {
            els.liveDot.classList.add('reconnecting');
            els.liveText.textContent = 'Reconnecting...';
        }
    }

    function render(data) {
        const servingText = data.currently_serving ?? 'None';
        const positionText = '#' + data.queue_position;

        if (positionText !== els.queuePosition.textContent.trim()) bump(els.queuePosition);
        if (String(data.people_ahead) !== els.peopleAhead.textContent.trim()) bump(els.peopleAhead);
        if (String(servingText) !== els.currentlyServing.textContent.trim()) bump(els.currentlyServing);

        els.queuePosition.textContent = positionText;
        els.peopleAhead.textContent = data.people_ahead;
        els.currentlyServing.textContent = servingText;
        els.stateLine.textContent = data.queue_state;
        els.laneLabel.textContent = 'Lane: ' + (data.lane_label ?? 'General Queue');

        const stageMap = {
            'Submitted': 0,
            'In Review': 1,
            'Awaiting Student Response': 1,
            'Appointment Required': 1,
            'Appointment Scheduled': 1,
            'Resolved': 2,
            'Closed': 2
        };
        const activeStage = stageMap[data.status] ?? 0;
        const stageProgress = ((activeStage / 2) * 100).toFixed(0) + '%';
        els.statusStages.style.setProperty('--stage-progress', stageProgress);
        Array.from(els.statusStages.children).forEach((stage, index) => {
            const wasActive = stage.classList.contains('active');
            const nowActive = index <= activeStage;
            const nowCurrent = index === activeStage;
            const icon = stage.querySelector('.stage-icon');

            stage.classList.toggle('active', nowActive);
            stage.classList.toggle('current', nowCurrent);

            const iconClass = nowCurrent
                ? (index === 0 ? 'bi-hourglass-split' : index === 1 ? 'bi-activity' : 'bi-check-circle-fill')
                : nowActive
                    ? 'bi-check2-circle'
                    : (index === 0 ? 'bi-send-fill' : index === 1 ? 'bi-search' : 'bi-flag-fill');

            if (icon && !icon.classList.contains(iconClass)) {
                icon.classList.add('morphing');
                setTimeout(() => {
                    icon.className = 'bi ' + iconClass + ' stage-icon';
                    setTimeout(() => icon.classList.remove('morphing'), 35);
                }, 130);
            }

            if (!wasActive && nowActive) {
                stage.classList.remove('stage-flash');
                requestAnimationFrame(() => stage.classList.add('stage-flash'));
                setTimeout(() => stage.classList.remove('stage-flash'), 560);
            }
        });

        const totalQueue = Math.max(data.queue_position + data.people_ahead, 1);
        const progress = Math.round((totalQueue - data.queue_position) / totalQueue * 100);
        els.progressBar.style.width = progress + '%';
        els.progressBar.textContent = progress + '%';
        els.progressBar.setAttribute('aria-valuenow', String(progress));

        if (previous.currently_serving !== data.currently_serving && data.currently_serving !== null) {
            showEvent('Queue moved: now serving #' + data.currently_serving);
            playSoftCue();
        } else if (previous.queue_position !== data.queue_position) {
            showEvent('Queue moved: your position is now #' + data.queue_position);
            playSoftCue();
        } else if (previous.people_ahead !== data.people_ahead) {
            showEvent('People ahead changed to ' + data.people_ahead);
            playSoftCue();
        } else if (previous.status !== data.status) {
            showEvent('Status updated: ' + data.status);
            playSoftCue();
        }

        previous = {
            queue_position: data.queue_position,
            people_ahead: data.people_ahead,
            currently_serving: data.currently_serving,
            status: data.status,
        };
    }

    async function fetchQueueStatus() {
        if (pending) return;
        pending = true;
        try {
            const response = await fetch("{{ route('student.requests.queueStatus', $request->id) }}", {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('Request failed');
            const data = await response.json();
            setLiveState(true);
            render(data);
        } catch (error) {
            setLiveState(false);
        } finally {
            pending = false;
        }
    }

    function bindRealtime() {
        if (realtimeBound || !window.UQSEcho) {
            return;
        }

        realtimeBound = true;
        window.UQSEcho.channel(`office.${activeOfficeId}`)
            .listen('.queue.lane.updated', () => {
                setLiveState(true);
                fetchQueueStatus();
            });

        window.UQSEcho.private(`student.request.${requestId}`)
            .listen('.service-request.updated', (event) => {
                setLiveState(true);
                render(event);

                if (event.office_id && Number(event.office_id) !== Number(activeOfficeId)) {
                    window.UQSEcho.leave(`office.${activeOfficeId}`);
                    activeOfficeId = Number(event.office_id);
                    window.UQSEcho.channel(`office.${activeOfficeId}`)
                        .listen('.queue.lane.updated', () => {
                            setLiveState(true);
                            fetchQueueStatus();
                        });
                }
            });
    }

    fetchQueueStatus();
    bindRealtime();
    document.addEventListener('uqs:echo-ready', bindRealtime);
    (function initSoundToggle() {
        if (!els.soundToggle) return;
        const saved = localStorage.getItem(SOUND_KEY) === '1';
        els.soundToggle.checked = saved;
        els.soundToggle.addEventListener('change', function () {
            localStorage.setItem(SOUND_KEY, els.soundToggle.checked ? '1' : '0');
            if (els.soundToggle.checked) {
                unlockAudio();
                playSoftCue();
            }
        });
    })();
    document.addEventListener('pointerdown', unlockAudio, { passive: true });
    document.addEventListener('keydown', unlockAudio);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            unlockAudio();
        }
    });
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            fetchQueueStatus();
        }
    }, 15000);
</script>
@endif

@endsection
