<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $office->name }} Queue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd, #20c997);
            min-height: 100vh;
            color: white;
        }
        body.dark-mode {
            background: linear-gradient(135deg, #0b1220, #1b2b45);
            color: #dbe4f0;
        }

        .card {
            border-radius: 20px;
        }

        .lane-card {
            min-height: 100%;
            position: relative;
            overflow: hidden;
        }
        body.dark-mode .lane-card {
            background: #111a2d !important;
            color: #dbe4f0 !important;
            border: 1px solid #2b3d5d !important;
        }
        body.dark-mode .list-group-item {
            background: #111a2d !important;
            color: #dbe4f0 !important;
            border-color: #2b3d5d !important;
        }

        .lane-card::after {
            content: "";
            position: absolute;
            inset: -90% -45%;
            background: radial-gradient(circle, rgba(13, 110, 253, 0.2), transparent 58%);
            pointer-events: none;
            animation: laneGlow 3.6s linear infinite;
        }

        .live-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(25, 135, 84, 0.18);
            color: #fff;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .tv-topbar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: .65rem;
        }
        .sound-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.28);
        }
        .sound-pill .form-check {
            margin: 0;
            min-height: auto;
        }
        .sound-pill .form-check-input {
            margin-top: 0;
        }
        .sound-pill .form-check-label {
            color: #fff;
            font-size: .86rem;
            font-weight: 600;
            letter-spacing: .01em;
        }

        .live-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #7dffb6;
            box-shadow: 0 0 0 0 rgba(125, 255, 182, 0.75);
            animation: pulse 1.8s infinite;
        }

        .live-dot.reconnecting {
            background: #ffc078;
            box-shadow: 0 0 0 0 rgba(255, 192, 120, 0.75);
        }

        .metric-bump {
            transition: transform 0.24s ease;
        }

        .metric-bump.bump {
            transform: scale(1.1);
        }

        .fade-up {
            animation: fadeUp .35s ease;
        }
        .tokens-grid {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            padding-top: .3rem;
        }
        .token-chip {
            min-width: 112px;
            padding: .55rem .8rem;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            color: #0f172a;
            text-align: center;
            font-weight: 700;
            letter-spacing: .03em;
            font-size: 1.1rem;
            line-height: 1.1;
        }
        .audio-controls {
            display: inline-block;
            margin-top: 0;
        }
        .single-lane-mode .lane-item {
            flex: 0 0 100%;
            max-width: 100%;
        }
        .single-lane-mode .lane-card {
            min-height: 72vh;
            padding: 2rem !important;
        }
        .single-lane-mode .lane-current {
            font-size: clamp(2.8rem, 9vw, 5rem);
            letter-spacing: .04em;
        }
        .single-lane-mode .token-chip {
            min-width: 140px;
            font-size: 1.35rem;
            padding: .75rem 1rem;
        }
        body.dark-mode .token-chip {
            background: #1b2a40;
            border-color: #304563;
            color: #e2e8f0;
        }
        .no-lane-card {
            border: none;
            border-radius: 22px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(11, 18, 32, .95), rgba(18, 72, 125, .9));
            color: #fff;
        }
        .no-lane-radar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 2px solid rgba(125, 255, 182, .9);
            position: relative;
            margin: 0 auto 1rem;
        }
        .no-lane-radar::before,
        .no-lane-radar::after {
            content: "";
            position: absolute;
            inset: 10px;
            border-radius: 50%;
            border: 2px solid rgba(125, 255, 182, .45);
        }
        .no-lane-radar::after {
            animation: radarPingTv 1.9s ease-out infinite;
        }
        .no-lane-sub {
            color: rgba(255, 255, 255, .82);
        }
        @keyframes radarPingTv {
            from { transform: scale(1); opacity: .85; }
            to { transform: scale(1.9); opacity: 0; }
        }

        @keyframes pulse {
            70% { box-shadow: 0 0 0 12px rgba(125, 255, 182, 0); }
            100% { box-shadow: 0 0 0 0 rgba(125, 255, 182, 0); }
        }

        @keyframes laneGlow {
            0% { transform: translateX(-10%); }
            100% { transform: translateX(10%); }
        }

        @keyframes fadeUp {
            from { opacity: .35; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 767.98px) {
            .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .card {
                border-radius: 14px;
            }
            .tv-topbar {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid py-5">
        <button id="themeTogglePublic" class="theme-toggle-btn" style="position:fixed;top:12px;right:12px;z-index:1200;border:1px solid rgba(255,255,255,.4);background:rgba(255,255,255,.9);">
            <span id="themeTogglePublicIcon">🌙</span>
        </button>
        <div class="text-center mb-5">
            <h1 class="fw-bold">{{ $office->name }}</h1>
            <p class="lead mb-2">Live Queue by Lane</p>
            <div class="tv-topbar">
                <span class="live-pill">
                    <span class="live-dot" id="live_dot"></span>
                    <span id="live_text">Live</span>
                </span>
                <div class="audio-controls sound-pill">
                    <label class="form-check form-switch d-inline-flex align-items-center gap-2">
                        <input class="form-check-input" type="checkbox" id="queue_sound_toggle">
                        <span class="form-check-label">Sound</span>
                    </label>
                </div>
            </div>
        </div>

        <div id="lanes_container" class="row g-4 {{ $lanes->count() === 1 ? 'single-lane-mode' : '' }}">
            @if($lanes->isEmpty())
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-lg text-center p-5 no-lane-card">
                        <div class="no-lane-radar"></div>
                        <h2 class="mb-2">No Live Queue Yet</h2>
                        <p class="mb-0 no-lane-sub">Waiting for the first token to enter this office queue.</p>
                    </div>
                </div>
            @else
                @foreach($lanes as $lane)
                    <div class="{{ $lanes->count() === 1 ? 'col-12' : 'col-lg-6' }} lane-item" data-lane-label="{{ $lane['label'] }}">
                        <div class="card shadow-lg p-4 bg-light text-dark lane-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0">{{ $lane['label'] }}</h4>
                                <span class="badge {{ $lane['state'] === 'Queue not started yet' ? 'bg-warning text-dark' : 'bg-success' }} lane-state">
                                    {{ $lane['state'] }}
                                </span>
                            </div>

                            <div class="card bg-dark text-white p-3 mb-3 text-center metric-bump lane-current-card">
                                <h6 class="mb-2">Currently Serving</h6>
                                <div class="h4 mb-1 lane-current">{{ optional($lane['current'])->token_code ?? 'None' }}</div>
                                <small class="lane-counter">
                                    @if(optional($lane['current'])->token_code)
                                        {{ 'Now serving ' . optional($lane['current'])->token_code . (optional($lane['current'])->serving_counter ? ' at ' . optional($lane['current'])->serving_counter : '') }}
                                    @endif
                                </small>
                            </div>

                            <h6>Upcoming Tokens</h6>
                            <div class="tokens-grid lane-next-list">
                                @foreach(($lane['called'] ?? collect()) as $calledReq)
                                    <div class="token-chip">
                                        {{ $calledReq->token_code }}
                                    </div>
                                @endforeach
                                @forelse($lane['next'] as $req)
                                    <div class="token-chip">
                                        {{ $req->token_code }}
                                    </div>
                                @empty
                                    @if(empty($lane['called']) || collect($lane['called'])->isEmpty())
                                        <div class="text-muted">No waiting requests</div>
                                    @endif
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    @include('layouts.realtime-scripts')
    <script>
        (function () {
            const isDark = localStorage.getItem('uqs-theme') === 'dark';
            document.body.classList.toggle('dark-mode', isDark);
            const icon = document.getElementById('themeTogglePublicIcon');
            if (icon) icon.textContent = isDark ? '☀️' : '🌙';
        })();

        document.getElementById('themeTogglePublic')?.addEventListener('click', function () {
            const willBeDark = !document.body.classList.contains('dark-mode');
            document.body.classList.toggle('dark-mode', willBeDark);
            localStorage.setItem('uqs-theme', willBeDark ? 'dark' : 'light');
            document.getElementById('themeTogglePublicIcon').textContent = willBeDark ? '☀️' : '🌙';
        });

        const lanesContainer = document.getElementById('lanes_container');
        const liveDot = document.getElementById('live_dot');
        const liveText = document.getElementById('live_text');
        const soundToggle = document.getElementById('queue_sound_toggle');
        const officeId = {{ (int) $office->id }};
        const isChrome = /Chrome/.test(navigator.userAgent) && !/Edg|OPR|Brave/.test(navigator.userAgent);
        let isPending = false;
        let hasRealtimeBinding = false;
        let hasRenderedOnce = false;
        let previousCurrentByLane = new Map();
        let audioCtx = null;
        let audioUnlocked = false;
        let pendingAnnouncement = '';
        const SOUND_KEY = 'uqs-tv-queue-sound-enabled';

        function isSoundEnabled() {
            return !!soundToggle?.checked;
        }

        async function unlockAudio() {
            if (!isSoundEnabled() || audioUnlocked) return;
            try {
                audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
                if (audioCtx.state === 'suspended') {
                    await audioCtx.resume();
                }
                if (window.speechSynthesis && typeof window.speechSynthesis.resume === 'function') {
                    window.speechSynthesis.resume();
                    window.speechSynthesis.getVoices();
                }
                audioUnlocked = true;
                if (pendingAnnouncement) {
                    const queued = pendingAnnouncement;
                    pendingAnnouncement = '';
                    speakAnnouncement(queued);
                }
            } catch (_) {
                // Ignore unlock errors
            }
        }

        function playSoftCue() {
            if (!isSoundEnabled()) return;
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
                return true;
            } catch (_) {
                // Ignore audio errors
            }
            return false;
        }

        function speakAnnouncement(message) {
            if (!isSoundEnabled() || !window.speechSynthesis) return;
            if (!audioUnlocked) {
                pendingAnnouncement = message;
                return;
            }
            try {
                window.speechSynthesis.cancel();
                if (typeof window.speechSynthesis.resume === 'function') {
                    window.speechSynthesis.resume();
                }
                const utter = new SpeechSynthesisUtterance(message);
                utter.rate = 0.98;
                utter.pitch = 1;
                utter.volume = 1;
                window.speechSynthesis.speak(utter);
                pendingAnnouncement = '';
            } catch (_) {
                pendingAnnouncement = message;
            }
        }

        function announceChanges(lanes) {
            const changed = [];
            const nextMap = new Map();

            lanes.forEach((lane) => {
                const current = lane.current_token ?? 'None';
                nextMap.set(lane.label, current);
                const previous = previousCurrentByLane.get(lane.label) ?? 'None';
                if (hasRenderedOnce && current !== previous && current !== 'None') {
                    changed.push({ lane: lane.label, token: current, counter: lane.current_counter ?? '' });
                }
            });

            previousCurrentByLane = nextMap;

            if (!changed.length) return;
            playSoftCue();
            setTimeout(playSoftCue, 220);
            const primary = changed[0];
            const atLabel = primary.counter ? ` at ${primary.counter}` : '';
            // On Chrome, try speaking regardless of chime success to keep automatic announcements.
            speakAnnouncement(`Now serving ${primary.token} at ${primary.lane}${atLabel}`);
        }

        function setConnectionState(isOnline) {
            if (isOnline) {
                liveDot.classList.remove('reconnecting');
                liveText.textContent = 'Live';
            } else {
                liveDot.classList.add('reconnecting');
                liveText.textContent = 'Reconnecting...';
            }
        }

        function laneCardHtml(lane, isSingleLane = false) {
            const stateClass = lane.state === 'Queue not started yet' ? 'bg-warning text-dark' : 'bg-success';
            const current = lane.current_token ?? 'None';
            const currentCounter = lane.current_counter ?? '';
            const calledTokens = Array.isArray(lane.called) ? lane.called : [];
            let nextHtml = '';

            if (!calledTokens.length && !lane.next.length) {
                nextHtml = '<div class="text-muted">No waiting requests</div>';
            } else {
                const calledHtml = calledTokens.map(item => {
                    return `<div class="token-chip fade-up">${item.token_code ?? item.queue_position}</div>`;
                }).join('');
                const waitingHtml = lane.next.map(item => {
                    return `<div class="token-chip fade-up">${item.token_code ?? item.queue_position}</div>`;
                }).join('');
                nextHtml = calledHtml + waitingHtml;
            }

            return `
                <div class="${isSingleLane ? 'col-12' : 'col-lg-6'} lane-item" data-lane-label="${lane.label}">
                    <div class="card shadow-lg p-4 bg-light text-dark lane-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">${lane.label}</h4>
                            <span class="badge ${stateClass} lane-state">${lane.state}</span>
                        </div>
                        <div class="card bg-dark text-white p-3 mb-3 text-center metric-bump lane-current-card">
                            <h6 class="mb-2">Currently Serving</h6>
                            <div class="h4 mb-1 lane-current">${current}</div>
                            <small class="lane-counter">${current !== 'None' ? ('Now serving ' + current + (currentCounter ? (' at ' + currentCounter) : '')) : ''}</small>
                        </div>
                        <h6>Upcoming Tokens</h6>
                        <div class="tokens-grid lane-next-list">${nextHtml}</div>
                    </div>
                </div>
            `;
        }

        function renderLanes(lanes) {
            if (!lanes.length) {
                lanesContainer.classList.remove('single-lane-mode');
                lanesContainer.innerHTML = `
                    <div class="col-lg-8 mx-auto">
                        <div class="card shadow-lg text-center p-5 no-lane-card fade-up">
                            <div class="no-lane-radar"></div>
                            <h2 class="mb-2">No Live Queue Yet</h2>
                            <p class="mb-0 no-lane-sub">Waiting for the first token to enter this office queue.</p>
                        </div>
                    </div>
                `;
                return;
            }

            if (!hasRenderedOnce) {
                const initialLane = lanes.find((lane) => (lane.current_token ?? 'None') !== 'None');
                if (initialLane) {
                    const token = initialLane.current_token;
                    const atLabel = initialLane.current_counter ? ` at ${initialLane.current_counter}` : '';
                    playSoftCue();
                    setTimeout(playSoftCue, 220);
                    speakAnnouncement(`Now serving ${token} at ${initialLane.label}${atLabel}`);
                }
            }

            announceChanges(lanes);
            const isSingleLane = lanes.length === 1;
            lanesContainer.classList.toggle('single-lane-mode', isSingleLane);
            lanesContainer.innerHTML = lanes.map((lane) => laneCardHtml(lane, isSingleLane)).join('');
            hasRenderedOnce = true;

            document.querySelectorAll('.lane-current-card').forEach((el) => {
                el.classList.add('bump');
                setTimeout(() => el.classList.remove('bump'), 260);
            });
        }

        async function fetchQueueBoard() {
            if (isPending) return;
            isPending = true;
            try {
                const response = await fetch("{{ route('queue.public.status', $office->id) }}", {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Request failed');
                const data = await response.json();
                setConnectionState(true);
                renderLanes(data.lanes || []);
            } catch (error) {
                setConnectionState(false);
            } finally {
                isPending = false;
            }
        }

        function bindRealtimeUpdates() {
            if (hasRealtimeBinding || !window.UQSEcho) {
                return;
            }

            hasRealtimeBinding = true;
            window.UQSEcho.channel(`office.${officeId}`)
                .listen('.queue.lane.updated', () => {
                    setConnectionState(true);
                    fetchQueueBoard();
                });
        }

        fetchQueueBoard();
        (function initSoundToggle() {
            if (!soundToggle) return;
            const savedValue = localStorage.getItem(SOUND_KEY);
            const saved = savedValue === null ? true : savedValue === '1';
            soundToggle.checked = saved;
            if (savedValue === null && isChrome) {
                localStorage.setItem(SOUND_KEY, '1');
            }
            soundToggle.addEventListener('change', function () {
                localStorage.setItem(SOUND_KEY, soundToggle.checked ? '1' : '0');
                if (soundToggle.checked) {
                    unlockAudio();
                    playSoftCue();
                } else if (window.speechSynthesis) {
                    window.speechSynthesis.cancel();
                }
            });
        })();
        // Keep trying to unlock audio automatically for Chrome TV tab.
        if (isChrome) {
            unlockAudio();
            setInterval(() => {
                if (isSoundEnabled()) {
                    unlockAudio();
                }
            }, 3000);
        }
        document.addEventListener('pointerdown', unlockAudio, { passive: true });
        document.addEventListener('touchstart', unlockAudio, { passive: true });
        document.addEventListener('keydown', unlockAudio);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                unlockAudio();
            }
        });
        bindRealtimeUpdates();
        document.addEventListener('uqs:echo-ready', bindRealtimeUpdates);
        // Keep polling even in background tab so announcements still trigger.
        setInterval(fetchQueueBoard, 6000);
    </script>
</body>

</html>
