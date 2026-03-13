@extends('layouts.app')
@section('title', 'Queue Operations')

@section('content')
<div class="container-fluid py-3 queue-ops-page">
    <div class="queue-ops-header">
        <div class="queue-ops-header-text">
            <h3 class="mb-1">Queue Operations</h3>
            <p class="text-muted mb-0">Live lane control for walk-ins, appointments, and submitted requests.</p>
        </div>
        <div class="queue-ops-header-action">
            <form action="{{ route('staff.queue.operations.toggle') }}" method="POST" class="queue-toggle-form">
                @csrf
                <div class="queue-toggle-stack">
                    <label class="form-check form-switch queue-master-switch mb-0">
                        <input
                            id="queue_ops_toggle"
                            class="form-check-input"
                            type="checkbox"
                            name="queue_operations_enabled"
                            value="1"
                            {{ $isQueueOperationsEnabled ? 'checked' : '' }}
                        >
                        <span class="form-check-label" id="queue_ops_toggle_label">
                            {{ $isQueueOperationsEnabled ? 'ON' : 'OFF' }}
                        </span>
                    </label>
                    <div class="queue-toggle-caption">Queue Operations</div>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="queue-stats-grid mb-3 queue-ops-stats">
        <div>
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted mb-1">Now Serving</div>
                    <div class="h4 mb-0" id="stat_now_serving">{{ $nowServing?->token_code ?? 'None' }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted mb-1">Total Tokens Left</div>
                    <div class="h4 mb-0" id="stat_total_tokens">{{ $totalPendingTokens }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted mb-1">Online Requests</div>
                    <div class="h4 mb-0" id="stat_waiting_online_requests">{{ $waitingOnlineRequests }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted mb-1">Walk-ins Waiting</div>
                    <div class="h4 mb-0" id="stat_waiting_walkins">{{ $waitingWalkIns }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted mb-1">Appointments Waiting</div>
                    <div class="h4 mb-0" id="stat_waiting_appointments">{{ $waitingAppointments }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 queue-ops-grid">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100 queue-panel">
                <div class="card-body service-control-body">
                    <div class="serving-head mb-3">
                        <h5 class="mb-0">Serving Control</h5>
                        <div class="serving-meta">
                            <div class="serving-badges">
                                <span class="badge {{ $isOfficeOpen ? 'bg-success' : 'bg-danger' }}" id="office_open_badge">
                                    {{ $isOfficeOpen ? 'Office Open' : 'Office Closed' }}
                                </span>
                                <span class="badge {{ $isQueueOperationsEnabled ? 'bg-primary' : 'bg-secondary' }}" id="queue_ops_badge">
                                    {{ $isQueueOperationsEnabled ? 'Queue Ops Enabled' : 'Queue Ops Paused' }}
                                </span>
                                <span class="badge {{ $isWalkInEnabled ? 'bg-info text-dark' : 'bg-secondary' }}" id="walk_in_badge">
                                    {{ $isWalkInEnabled ? 'Walk-ins Open' : 'Walk-ins Closed' }}
                                </span>
                            </div>
                            <form action="{{ route('staff.queue.walk-ins.toggle') }}" method="POST" class="serving-action">
                                @csrf
                                <input type="hidden" name="walk_in_enabled" value="{{ $isWalkInEnabled ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm {{ $isWalkInEnabled ? 'btn-outline-secondary' : 'btn-outline-info' }}">
                                    {{ $isWalkInEnabled ? 'Close Walk-ins' : 'Open Walk-ins' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <form action="{{ route('staff.queue.call-next') }}" method="POST" class="mb-4">
                        @csrf
                        <button type="submit" id="call_next_btn" class="btn btn-lg w-100 queue-next-btn" {{ $isQueueOperationsEnabled ? '' : 'disabled' }}>
                            {{ $isQueueOperationsEnabled ? 'CALL NEXT TOKEN' : 'QUEUE PAUSED (MANUAL)' }}
                        </button>
                    </form>

                    <h6 class="mb-2">Recent Queue Flow</h6>
                    <div class="queue-feed" id="queue_feed">
                        @forelse($recentQueueEvents as $event)
                            <div class="queue-feed-item">
                                <div class="queue-feed-main">
                                    <strong>{{ $event->token_code }}</strong>
                                    <small class="queue-feed-meta">{{ strtoupper($event->request_mode) }} • {{ optional($event->serviceType)->name }}</small>
                                </div>
                                <span class="badge bg-dark">{{ strtoupper(str_replace('_', ' ', $event->queue_stage)) }}</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No queue activity yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm h-100 queue-panel">
                <div class="card-body">
                    <h5 class="mb-3">Add Walk-in</h5>
                    <div class="alert {{ $isWalkInEnabled ? 'alert-success' : 'alert-warning' }} py-2" id="walkin_state_alert">
                        {{ $isWalkInEnabled ? 'Walk-ins are active for this lane.' : 'Walk-ins are currently closed for this lane.' }}
                    </div>
                    <form action="{{ route('staff.queue.walk-ins.store') }}" method="POST" id="walkin_form">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Student Number</label>
                            <input type="text" name="student_number" class="form-control walkin-input" placeholder="e.g. 21484/2023" required {{ $isWalkInEnabled ? '' : 'disabled' }}>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (optional)</label>
                            <textarea name="description" rows="3" class="form-control walkin-input" placeholder="Quick walk-in issue details" {{ $isWalkInEnabled ? '' : 'disabled' }}></textarea>
                        </div>
                        <button class="btn btn-primary w-100" id="add_walkin_btn" {{ $isWalkInEnabled ? '' : 'disabled' }}>Add Walk-in Token</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .queue-ops-page {
        --queue-accent: #2563eb;
        --queue-pink: #be123c;
        --queue-pink-soft: #fff1f2;
    }
    .queue-ops-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: start;
        margin-bottom: 1rem;
    }
    .queue-ops-header-text h3 {
        line-height: 1.1;
    }
    .queue-ops-header-text p {
        line-height: 1.35;
    }
    .queue-toggle-form {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: .6rem .9rem;
    }
    .queue-toggle-stack {
        display: grid;
        justify-items: center;
        gap: .35rem;
    }
    .queue-toggle-caption {
        font-size: .8rem;
        color: #334155;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .queue-master-switch .form-check-input {
        margin-top: 0;
        width: 3.2rem;
        height: 1.7rem;
        cursor: pointer;
    }
    .queue-master-switch .form-check-label {
        font-weight: 600;
        color: #0f172a;
        margin-left: .55rem;
        min-width: 2.5rem;
        text-align: left;
        font-size: .95rem;
    }
    .queue-ops-stats.row {
        margin-top: 0;
        margin-bottom: 1rem !important;
        --bs-gutter-y: 1rem;
    }
    .queue-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: .75rem;
        margin-top: 0;
    }
    .queue-next-btn {
        background: linear-gradient(120deg, #0ea5e9, #2563eb, #14b8a6);
        border: none;
        color: #fff;
        font-weight: 700;
        letter-spacing: .08em;
        min-height: 92px;
        animation: glowPulse 1.6s ease-in-out infinite;
        border-radius: .85rem;
    }
    .queue-next-btn:disabled {
        opacity: 1;
        background: linear-gradient(135deg, #ffe4e6, #ffeef2 52%, #ffe4ea);
        color: #9f1239;
        border: 1px solid #fecdd3;
        box-shadow: 0 0 0 0 rgba(190, 24, 93, .36), 0 14px 28px rgba(190, 24, 93, .12);
        animation: pausedGlow 2s ease-in-out infinite;
    }
    .service-control-body {
        color: #0f172a;
    }
    .queue-next-btn:hover { color: #fff; filter: brightness(1.05); }
    .stat-card {
        border: none;
        border-radius: .85rem;
        background: linear-gradient(145deg, #ffffff, #f3f7ff);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
    }
    .stat-card .card-body {
        padding: .9rem .95rem;
    }
    .stat-card .h4 {
        font-size: clamp(1.25rem, 2.4vw, 2rem);
        line-height: 1.1;
        letter-spacing: .01em;
        word-break: break-word;
    }
    .serving-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: .75rem;
        align-items: start;
    }
    .serving-meta {
        display: grid;
        justify-items: end;
        gap: .55rem;
    }
    .serving-badges {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        justify-content: flex-end;
    }
    .serving-action .btn {
        white-space: nowrap;
    }
    .queue-panel {
        border: none;
        border-radius: .95rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }
    .queue-feed { max-height: 360px; overflow-y: auto; display: grid; gap: .6rem; }
    .queue-feed-item {
        border: 1px solid rgba(17, 24, 39, 0.08);
        border-radius: .7rem;
        padding: .75rem .85rem;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: .75rem;
        align-items: start;
        animation: slideIn .35s ease;
        background: #fff;
    }
    .queue-feed-main {
        min-width: 0;
    }
    .queue-feed-meta {
        display: block;
        margin-top: .2rem;
        color: #64748b;
        white-space: normal;
        word-break: break-word;
    }
    @keyframes glowPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, .35); transform: translateY(0); }
        50% { box-shadow: 0 0 0 14px rgba(37, 99, 235, 0); transform: translateY(-1px); }
    }
    @keyframes floatGlow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(54, 73, 230, .35), 0 16px 38px rgba(54, 73, 230, .35); transform: translateY(0); }
        50% { box-shadow: 0 0 0 14px rgba(54, 73, 230, 0), 0 22px 44px rgba(54, 73, 230, .28); transform: translateY(-1px); }
    }
    @keyframes pausedGlow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(190, 24, 93, .35), 0 14px 28px rgba(190, 24, 93, .12); }
        50% { box-shadow: 0 0 0 14px rgba(190, 24, 93, 0), 0 18px 34px rgba(190, 24, 93, .18); }
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes subtlePulse {
        0% { box-shadow: 0 0 0 0 rgba(14, 165, 233, .35); }
        100% { box-shadow: 0 0 0 10px rgba(14, 165, 233, 0); }
    }
    .pulse-change {
        animation: subtlePulse .7s ease;
    }
    body.dark-mode .stat-card, body.dark-mode .queue-feed-item {
        background: #121927;
        border-color: rgba(148, 163, 184, 0.2);
        color: #e5e7eb;
    }
    body.dark-mode .service-control-body {
        color: #e5e7eb;
    }
    body.dark-mode .queue-next-btn:disabled {
        background: linear-gradient(130deg, #3f1e2a, #402031);
        color: #fecdd3;
        border-color: #9f1239;
        box-shadow: 0 0 0 0 rgba(251, 113, 133, .28), 0 14px 28px rgba(0, 0, 0, .3);
    }
    body.dark-mode .queue-feed-meta {
        color: #9ca3af;
    }
    body.dark-mode .queue-panel,
    body.dark-mode .stat-card {
        box-shadow: 0 16px 34px rgba(0, 0, 0, .28);
    }
    body.dark-mode .queue-toggle-form {
        background: #101924;
        border-color: #334155;
    }
    body.dark-mode .queue-toggle-caption {
        color: #cbd5e1;
    }
    body.dark-mode .queue-master-switch .form-check-label {
        color: #e2e8f0;
    }
    .queue-float-wrap {
        position: fixed;
        right: 18px;
        bottom: 20px;
        z-index: 1020;
    }
    .queue-float-btn {
        border: none;
        border-radius: 999px;
        background: #3649e6;
        color: #fff;
        font-weight: 700;
        letter-spacing: .05em;
        padding: .95rem 1.4rem;
        box-shadow: 0 16px 38px rgba(54, 73, 230, .35), 0 0 18px rgba(54, 73, 230, .55);
        animation: floatGlow 1.6s ease-in-out infinite;
    }
    .queue-float-btn:hover { color: #fff; filter: brightness(1.03); }
    .queue-float-btn:disabled {
        opacity: .55;
        cursor: not-allowed;
        box-shadow: none;
        animation: none;
    }
    @media (max-width: 991.98px) {
        .queue-ops-header {
            grid-template-columns: 1fr;
        }
        .queue-ops-header-action {
            justify-self: start;
        }
        .queue-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .serving-head {
            grid-template-columns: 1fr;
        }
        .serving-meta {
            justify-items: start;
        }
        .serving-badges {
            justify-content: flex-start;
        }
        .queue-float-wrap {
            right: 12px;
            bottom: 12px;
        }
    }
    @media (min-width: 1200px) {
        .queue-stats-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .queue-next-btn,
        .queue-next-btn:disabled,
        .queue-float-btn,
        .queue-feed-item,
        .pulse-change {
            animation: none !important;
            transition: none !important;
        }
    }
</style>

<div class="queue-float-wrap">
    <form action="{{ route('staff.queue.advance-next') }}" method="POST">
        @csrf
        <button type="submit" id="call_next_btn_floating" class="queue-float-btn" {{ $isQueueOperationsEnabled ? '' : 'disabled' }}>
            COMPLETE + NEXT
        </button>
    </form>
</div>

<script>
    (function () {
        const statNowServing = document.getElementById('stat_now_serving');
        const statTotalTokens = document.getElementById('stat_total_tokens');
        const statOnlineRequests = document.getElementById('stat_waiting_online_requests');
        const statWalkIns = document.getElementById('stat_waiting_walkins');
        const statAppointments = document.getElementById('stat_waiting_appointments');
        const badge = document.getElementById('office_open_badge');
        const queueOpsBadge = document.getElementById('queue_ops_badge');
        const queueOpsToggle = document.getElementById('queue_ops_toggle');
        const queueOpsToggleLabel = document.getElementById('queue_ops_toggle_label');
        const walkInBadge = document.getElementById('walk_in_badge');
        const btnMain = document.getElementById('call_next_btn');
        const btnFloat = document.getElementById('call_next_btn_floating');
        const feed = document.getElementById('queue_feed');
        const addWalkInButton = document.getElementById('add_walkin_btn');
        const walkInInputs = Array.from(document.querySelectorAll('.walkin-input'));
        const walkInAlert = document.getElementById('walkin_state_alert');
        let previousEventsHash = '';

        let officeOpen = Boolean(@json($isOfficeOpen));
        let queueOpsEnabled = Boolean(@json($isQueueOperationsEnabled));

        const refreshQueueButtons = () => {
            const canOperate = queueOpsEnabled;
            btnMain.disabled = !canOperate;
            btnFloat.disabled = !canOperate;
            btnMain.textContent = canOperate
                ? 'CALL NEXT TOKEN'
                : 'QUEUE PAUSED (MANUAL)';
        };

        const applyOfficeState = (isOpen) => {
            officeOpen = !!isOpen;
            badge.classList.toggle('bg-success', isOpen);
            badge.classList.toggle('bg-danger', !isOpen);
            badge.textContent = isOpen ? 'Office Open' : 'Office Closed';
            refreshQueueButtons();
        };

        const applyQueueOpsState = (isEnabled) => {
            queueOpsEnabled = !!isEnabled;
            queueOpsBadge.classList.toggle('bg-primary', isEnabled);
            queueOpsBadge.classList.toggle('bg-secondary', !isEnabled);
            queueOpsBadge.textContent = isEnabled ? 'Queue Ops Enabled' : 'Queue Ops Paused';
            if (queueOpsToggle) queueOpsToggle.checked = isEnabled;
            if (queueOpsToggleLabel) queueOpsToggleLabel.textContent = isEnabled ? 'ON' : 'OFF';
            refreshQueueButtons();
        };

        const applyWalkInState = (isEnabled) => {
            walkInBadge.classList.toggle('bg-info', isEnabled);
            walkInBadge.classList.toggle('text-dark', isEnabled);
            walkInBadge.classList.toggle('bg-secondary', !isEnabled);
            walkInBadge.textContent = isEnabled ? 'Walk-ins Open' : 'Walk-ins Closed';
            if (walkInAlert) {
                walkInAlert.classList.toggle('alert-success', isEnabled);
                walkInAlert.classList.toggle('alert-warning', !isEnabled);
                walkInAlert.textContent = isEnabled
                    ? 'Walk-ins are active for this lane.'
                    : 'Walk-ins are currently closed for this lane.';
            }
            if (addWalkInButton) {
                addWalkInButton.disabled = !isEnabled;
            }
            walkInInputs.forEach((field) => {
                field.disabled = !isEnabled;
            });
        };

        const renderFeed = (events) => {
            if (!events.length) {
                feed.innerHTML = '<p class="text-muted mb-0">No queue activity yet.</p>';
                return;
            }
            feed.innerHTML = events.map((event) => `
                <div class="queue-feed-item">
                    <div class="queue-feed-main">
                        <strong>${event.token_code}</strong>
                        <small class="queue-feed-meta">${event.request_mode} • ${event.service_type ?? ''}</small>
                    </div>
                    <span class="badge bg-dark">${event.queue_stage}</span>
                </div>
            `).join('');
        };

        const refreshQueueStats = async () => {
            try {
                const response = await fetch("{{ route('staff.queue.operations.status') }}", {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) return;
                const data = await response.json();
                const nextNowServing = data.now_serving ?? 'None';
                const nextTotalTokens = String(data.total_pending_tokens ?? 0);
                const nextOnlineRequests = String(data.waiting_online_requests ?? 0);
                const nextWalkIns = String(data.waiting_walk_ins ?? 0);
                const nextAppointments = String(data.waiting_appointments ?? 0);

                if (statNowServing.textContent !== nextNowServing) {
                    statNowServing.textContent = nextNowServing;
                    statNowServing.classList.remove('pulse-change');
                    void statNowServing.offsetWidth;
                    statNowServing.classList.add('pulse-change');
                }

                if (statTotalTokens.textContent !== nextTotalTokens) {
                    statTotalTokens.textContent = nextTotalTokens;
                    statTotalTokens.classList.remove('pulse-change');
                    void statTotalTokens.offsetWidth;
                    statTotalTokens.classList.add('pulse-change');
                }

                if (statOnlineRequests.textContent !== nextOnlineRequests) {
                    statOnlineRequests.textContent = nextOnlineRequests;
                    statOnlineRequests.classList.remove('pulse-change');
                    void statOnlineRequests.offsetWidth;
                    statOnlineRequests.classList.add('pulse-change');
                }

                if (statWalkIns.textContent !== nextWalkIns) {
                    statWalkIns.textContent = nextWalkIns;
                    statWalkIns.classList.remove('pulse-change');
                    void statWalkIns.offsetWidth;
                    statWalkIns.classList.add('pulse-change');
                }

                if (statAppointments.textContent !== nextAppointments) {
                    statAppointments.textContent = nextAppointments;
                    statAppointments.classList.remove('pulse-change');
                    void statAppointments.offsetWidth;
                    statAppointments.classList.add('pulse-change');
                }

                applyOfficeState(Boolean(data.is_office_open));
                applyQueueOpsState(Boolean(data.is_queue_operations_enabled));
                applyWalkInState(Boolean(data.is_walk_in_enabled));
                const eventsHash = JSON.stringify(data.recent_queue_events ?? []);
                if (eventsHash !== previousEventsHash) {
                    previousEventsHash = eventsHash;
                    renderFeed(data.recent_queue_events ?? []);
                    feed.classList.remove('pulse-change');
                    void feed.offsetWidth;
                    feed.classList.add('pulse-change');
                }
            } catch (e) {
                // ignore transient fetch errors
            }
        };

        setInterval(() => {
            if (document.visibilityState === 'visible') {
                refreshQueueStats();
            }
        }, 6000);

        queueOpsToggle?.addEventListener('change', function () {
            this.form.submit();
        });
    })();
</script>
@endsection
