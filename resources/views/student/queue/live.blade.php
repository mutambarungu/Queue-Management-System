@extends('layouts.app')
@section('title', 'Live Queue')

@section('content')
<style>
    .student-live-wrap {
        background: linear-gradient(135deg, #00225a, #6f86ff);
        border-radius: 18px;
        min-height: calc(100vh - 170px);
        color: #fff;
        padding: 1.2rem;
    }
    body.dark-mode .student-live-wrap {
        background: linear-gradient(135deg, #0b1220, #1b2b45);
    }
    .student-live-top {
        text-align: center;
        margin-bottom: 1.4rem;
    }
    .student-live-top h3 {
        color: #fff;
        margin-bottom: .15rem;
    }
    .student-live-sub {
        color: rgba(255, 255, 255, .88);
        margin-bottom: .45rem;
    }
    .tv-topbar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: .65rem;
    }
    .live-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(13, 110, 253, 0.18);
        color: #fff;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.25);
    }
    .live-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #6f86ff;
        box-shadow: 0 0 0 0 rgba(111, 134, 255, 0.75);
        animation: pulse 1.8s infinite;
    }
    .live-dot.reconnecting {
        background: #a5b4fc;
        box-shadow: 0 0 0 0 rgba(165, 180, 252, 0.75);
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
    }
    .lane-card {
        min-height: 100%;
        position: relative;
        overflow: hidden;
        border-radius: 18px;
    }
    .lane-card::after {
        content: "";
        position: absolute;
        inset: -90% -45%;
        background: radial-gradient(circle, rgba(13, 110, 253, 0.2), transparent 58%);
        pointer-events: none;
        animation: laneGlow 3.6s linear infinite;
    }
    .tokens-grid {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        padding-top: .25rem;
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
        font-size: 1.05rem;
        line-height: 1.1;
    }
    .token-chip.my-token {
        color: #fff;
        border-color: #93c5fd;
        background: linear-gradient(145deg, #0d6efd, #6f86ff);
        box-shadow: 0 10px 22px rgba(13, 110, 253, .38);
        animation: myTokenGlow 1.8s ease-in-out infinite;
        transform: scale(1.05);
    }
    .lane-current.my-token-current {
        color: #c7d2fe;
        text-shadow: 0 0 14px rgba(111,134,255,.5);
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
        border: 2px solid rgba(111, 134, 255, .9);
        position: relative;
        margin: 0 auto 1rem;
    }
    .no-lane-radar::before,
    .no-lane-radar::after {
        content: "";
        position: absolute;
        inset: 10px;
        border-radius: 50%;
        border: 2px solid rgba(111, 134, 255, .45);
    }
    .no-lane-radar::after {
        animation: radarPing 1.9s ease-out infinite;
    }
    .no-lane-sub {
        color: rgba(255, 255, 255, .82);
    }
    .token-rule-note {
        color: rgba(255, 255, 255, .8);
        font-size: .86rem;
        margin-top: .9rem;
    }
    .student-focus-card {
        border-radius: 18px;
    }
    .student-token-big {
        display: inline-block;
        padding: .85rem 1.25rem;
        border-radius: 14px;
        font-size: clamp(1.8rem, 4vw, 2.4rem);
        font-weight: 800;
        letter-spacing: .06em;
        color: #fff;
        background: linear-gradient(145deg, #0d6efd, #6f86ff);
        border: 1px solid #93c5fd;
        box-shadow: 0 16px 30px rgba(13,110,253,.45);
        animation: myTokenGlow 1.8s ease-in-out infinite;
    }
    .ahead-token-chip {
        display: inline-block;
        padding: .4rem .7rem;
        border-radius: 999px;
        background: #0f172a;
        color: #fff;
        font-weight: 700;
        letter-spacing: .04em;
    }
    .token-collapse-btn {
        border-radius: 999px;
        font-weight: 600;
    }
    .queue-position-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        padding: .42rem .8rem;
        border-radius: 999px;
        background: rgba(13, 110, 253, .12);
        border: 1px solid rgba(13, 110, 253, .25);
        color: #0d6efd;
        font-weight: 700;
        font-size: .9rem;
    }
    @keyframes pulse {
        70% { box-shadow: 0 0 0 12px rgba(111, 134, 255, 0); }
        100% { box-shadow: 0 0 0 0 rgba(111, 134, 255, 0); }
    }
    @keyframes laneGlow {
        0% { transform: translateX(-10%); }
        100% { transform: translateX(10%); }
    }
    @keyframes myTokenGlow {
        0%,100% { box-shadow: 0 10px 22px rgba(13,110,253,.38), 0 0 0 0 rgba(111,134,255,.42); }
        50% { box-shadow: 0 14px 28px rgba(13,110,253,.45), 0 0 0 12px rgba(111,134,255,0); }
    }
    @keyframes radarPing {
        from { transform: scale(1); opacity: .85; }
        to { transform: scale(1.9); opacity: 0; }
    }
</style>

<div class="container-fluid py-3">
    <div class="student-live-wrap">
        <div class="student-live-top">
            <h3 class="fw-bold mb-1">{{ $office?->name ?? 'Live Queue' }}</h3>
            <div class="student-live-sub">Live Queue by Lane</div>
            <div class="tv-topbar">
                <span class="live-pill">
                    <span class="live-dot" id="live_dot"></span>
                    <span id="live_text">Live</span>
                </span>
                <div class="sound-pill">
                    <label class="form-check form-switch d-inline-flex align-items-center gap-2">
                        <input class="form-check-input" type="checkbox" id="queue_sound_toggle">
                        <span class="form-check-label">Sound</span>
                    </label>
                </div>
            </div>
        </div>

        <div id="lanes_container" class="row g-4"></div>
    </div>
</div>

<script>
    (function () {
        const lanesContainer = document.getElementById('lanes_container');
        const liveDot = document.getElementById('live_dot');
        const liveText = document.getElementById('live_text');
        const soundToggle = document.getElementById('queue_sound_toggle');
        const myToken = @json($myToken);
        const myLane = @json($myLane);
        const statusUrl = @json($watchOffice ? route('queue.public.status', $watchOffice->id) : null);
        const SOUND_KEY = 'uqs-student-live-queue-sound';
        let previousCurrentByLane = new Map();
        let hasRenderedOnce = false;
        let audioCtx = null;
        let audioUnlocked = false;
        let pending = false;

        const soundOn = () => !!soundToggle?.checked;
        const setConn = (ok) => {
            liveDot.classList.toggle('reconnecting', !ok);
            liveText.textContent = ok ? 'Live' : 'Reconnecting...';
        };

        const renderEmpty = () => {
            lanesContainer.innerHTML = `
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-lg text-center p-5 no-lane-card">
                        <div class="no-lane-radar"></div>
                        <h2 class="mb-2">No Active Token Yet</h2>
                        <p class="mb-0 no-lane-sub">No token in the queue.</p>
                        <p class="mb-0 token-rule-note">Tokens are generated only by staff or by students after scanning an office QR code.</p>
                    </div>
                </div>
            `;
        };

        const playCue = () => {
            if (!soundOn()) return;
            try {
                audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
                if (audioCtx.state === 'suspended') audioCtx.resume().catch(() => {});
                const now = audioCtx.currentTime;
                const gain = audioCtx.createGain();
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.08, now + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.32);
                gain.connect(audioCtx.destination);
                const osc = audioCtx.createOscillator();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(920, now);
                osc.connect(gain);
                osc.start(now);
                osc.stop(now + 0.28);
            } catch (_) {}
        };

        const unlockAudio = () => {
            if (audioUnlocked) return;
            try {
                audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume().catch(() => {});
                }
                if (window.speechSynthesis && typeof window.speechSynthesis.resume === 'function') {
                    window.speechSynthesis.resume();
                    window.speechSynthesis.getVoices();
                }
                audioUnlocked = true;
            } catch (_) {}
        };

        const speak = (text) => {
            if (!soundOn() || !window.speechSynthesis || !text) return;
            try {
                window.speechSynthesis.cancel();
                if (typeof window.speechSynthesis.resume === 'function') {
                    window.speechSynthesis.resume();
                }
                const u = new SpeechSynthesisUtterance(text);
                u.rate = 0.98;
                u.pitch = 1.0;
                u.volume = 1.0;
                window.speechSynthesis.speak(u);
            } catch (_) {}
        };

        const focusedLaneHtml = (lane) => {
            const current = lane.current_token ?? 'None';
            const called = Array.isArray(lane.called) ? lane.called.map((item) => item.token_code).filter(Boolean) : [];
            const allNext = Array.isArray(lane.next) ? lane.next.map((item) => item.token_code).filter(Boolean) : [];
            const combinedQueue = [
                ...(current !== 'None' ? [current] : []),
                ...called,
                ...allNext
            ];
            const fullQueueList = [];
            const seenTokens = new Set();
            combinedQueue.forEach((token) => {
                if (!seenTokens.has(token)) {
                    seenTokens.add(token);
                    fullQueueList.push(token);
                }
            });
            let myQueueIndex = fullQueueList.indexOf(myToken);
            if (myQueueIndex < 0 && current === myToken) {
                myQueueIndex = 0;
            }
            const isCalledNow = current === myToken || called.includes(myToken);
            const queueStateText = isCalledNow
                ? 'Called to counter now'
                : (myQueueIndex >= 0 ? `Position #${myQueueIndex + 1} in queue` : 'Not in active queue list');

            const fullUpcoming = fullQueueList.length
                ? fullQueueList.map((token) => `<div class="token-chip ${token === myToken ? 'my-token' : ''}">${token}</div>`).join('')
                : '<div class="text-muted">No waiting requests</div>';

            return `
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-lg p-4 bg-light text-dark lane-card student-focus-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">${lane.label || (myLane || 'General Queue')}</h4>
                            <span class="badge ${lane.state === 'Queue not started yet' ? 'bg-warning text-dark' : 'bg-primary'}">${lane.state || 'Queue active'}</span>
                        </div>
                        <div class="text-center mb-3">
                            <div class="small text-uppercase text-muted mb-1">Your Token</div>
                            <div class="student-token-big">${myToken}</div>
                        </div>
                        <div class="text-center mb-3">
                            <div class="small text-uppercase text-muted mb-1">Your Position In Queue</div>
                            <div>
                                ${isCalledNow
                                    ? '<span class="queue-position-pill">Called</span>'
                                    : myQueueIndex >= 0
                                    ? `<span class="queue-position-pill">#${myQueueIndex + 1} of ${fullQueueList.length}</span>`
                                    : '<span class="queue-position-pill">Not listed</span>'
                                }
                            </div>
                            <div class="small text-muted mt-2">${queueStateText}</div>
                        </div>
                        <div class="text-center">
                            <button class="btn btn-outline-secondary btn-sm token-collapse-btn mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#full_upcoming_tokens">
                                Show Full Upcoming Tokens
                            </button>
                            <div class="collapse" id="full_upcoming_tokens">
                                <div class="tokens-grid justify-content-center">${fullUpcoming}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        };

        const announceChanges = (lanes) => {
            const changed = [];
            const nextMap = new Map();

            lanes.forEach((lane) => {
                const current = lane.current_token ?? 'None';
                nextMap.set(lane.label, current);
                const previous = previousCurrentByLane.get(lane.label) ?? 'None';
                if (hasRenderedOnce && current !== previous && current !== 'None') {
                    changed.push({ lane: lane.label, token: current });
                }
            });

            previousCurrentByLane = nextMap;
            if (!changed.length) return;

            const first = changed[0];
            playCue();
            // Retry cue shortly to improve reliability on constrained mobile browsers.
            setTimeout(playCue, 220);
            if (first.token === myToken) {
                speak(`Your token ${myToken} is now serving at ${myLane || first.lane}`);
            } else {
                speak(`Now serving ${first.token} at ${first.lane}`);
            }
        };

        const render = (lanes) => {
            if (!statusUrl || !myToken || !Array.isArray(lanes) || !lanes.length) {
                renderEmpty();
                return;
            }
            announceChanges(lanes);
            const focusedLane = lanes.find((lane) =>
                lane.current_token === myToken
                || (Array.isArray(lane.called) && lane.called.some((item) => item.token_code === myToken))
                || (Array.isArray(lane.next) && lane.next.some((item) => item.token_code === myToken))
            );

            if (!focusedLane) {
                renderEmpty();
                return;
            }

            lanesContainer.innerHTML = focusedLaneHtml(focusedLane);
            hasRenderedOnce = true;
        };

        const refresh = async () => {
            if (!statusUrl || pending) {
                if (!statusUrl) renderEmpty();
                return;
            }
            pending = true;
            try {
                const response = await fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('fetch failed');
                const data = await response.json();
                setConn(true);
                render(data.lanes || []);
            } catch (_) {
                setConn(false);
            } finally {
                pending = false;
            }
        };

        if (soundToggle) {
            const saved = localStorage.getItem(SOUND_KEY);
                soundToggle.checked = saved === null ? true : saved === '1';
                soundToggle.addEventListener('change', () => {
                    localStorage.setItem(SOUND_KEY, soundToggle.checked ? '1' : '0');
                    if (soundToggle.checked) {
                        unlockAudio();
                        playCue();
                        speak('Sound enabled.');
                    }
                });
            }

            document.addEventListener('pointerdown', unlockAudio, { passive: true });
            document.addEventListener('touchstart', unlockAudio, { passive: true });
            document.addEventListener('keydown', unlockAudio);
            document.addEventListener('pointerdown', playCue, { passive: true });

        refresh();
        setInterval(() => {
            if (document.visibilityState === 'visible') refresh();
        }, 6000);
    })();
</script>
@endsection
