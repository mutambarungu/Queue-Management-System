@extends('layouts.app')
@section('title', 'Join Queue')

@section('content')
<style>
    .join-queue-page {
        min-height: 100vh;
        background:
            radial-gradient(circle at 15% 20%, rgba(15, 98, 254, 0.18), transparent 45%),
            radial-gradient(circle at 85% 80%, rgba(0, 167, 111, 0.16), transparent 50%),
            linear-gradient(135deg, #f6f8fb 0%, #eef3ff 100%);
        padding: 2rem 0;
    }
    .join-card {
        border: 1px solid #e8eefb;
        border-radius: 22px;
        background: #ffffff;
        box-shadow: 0 22px 65px rgba(19, 54, 110, 0.12);
    }
    .join-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: #eef6ff;
        color: #0a58ca;
        border-radius: 999px;
        padding: .35rem .75rem;
        font-weight: 600;
        font-size: .82rem;
    }
    .join-title {
        font-size: 1.8rem;
        font-weight: 800;
        color: #1e293b;
    }
    .join-note {
        color: #4c5d79;
    }
    .join-glow-btn {
        position: relative;
        border: none;
        background: linear-gradient(95deg, #0f62fe 0%, #00a76f 100%);
        color: #fff;
        font-weight: 700;
        letter-spacing: .01em;
        padding: .95rem 1.2rem;
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(15, 98, 254, 0.35);
        transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        animation: pulseGlow 1.8s ease-in-out infinite;
    }
    .join-glow-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 34px rgba(15, 98, 254, 0.45);
        filter: saturate(1.08);
    }
    .join-glow-btn:disabled {
        background: #94a3b8;
        box-shadow: none;
        animation: none;
        cursor: not-allowed;
    }
    @keyframes pulseGlow {
        0% { box-shadow: 0 0 0 0 rgba(15, 98, 254, .45), 0 12px 30px rgba(15, 98, 254, .30); }
        70% { box-shadow: 0 0 0 14px rgba(15, 98, 254, 0), 0 16px 35px rgba(15, 98, 254, .36); }
        100% { box-shadow: 0 0 0 0 rgba(15, 98, 254, 0), 0 12px 30px rgba(15, 98, 254, .30); }
    }
</style>
<div class="join-queue-page">
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-xl-6">
                <div class="join-card p-4 p-md-5">
                    <span class="join-badge mb-3">Queue Access</span>
                    <h3 class="join-title mb-2">Join Queue</h3>
                    <p class="join-note mb-4">
                        Scan confirmed for <strong>{{ $office->name }}</strong>{{ $subOffice ? ' / ' . $subOffice->name : ' / General Queue' }}.
                    </p>

                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    @if($canJoin)
                        <div class="alert alert-success mb-4">
                            Queue is active. Tap below to get your token immediately.
                        </div>
                    @else
                        <div class="alert alert-secondary mb-4">
                            <strong>Queue currently unavailable.</strong>
                            <div class="mt-1">
                                {{ $closureMessage ?: 'This office is not accepting queue joins right now.' }}
                                @if(!$isWalkInEnabled)
                                    <div class="mt-2">
                                        <strong>Walk-ins are closed by staff for this lane right now.</strong>
                                    </div>
                                    <div class="mt-1">
                                        Please wait until the office reopens walk-ins, or check with the help desk.
                                    </div>
                                @endif
                                @if(!empty($queueHours))
                                    <div class="mt-2">
                                        <span class="fw-semibold">Office hours:</span> {{ $queueHours }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if(session('joined_token'))
                        <div class="alert alert-success mb-4">
                            You joined the queue successfully. Your token is <strong>{{ session('joined_token') }}</strong>.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('queue.join.store') }}">
                        @csrf
                        <input type="hidden" name="office_id" value="{{ $office->id }}">
                        <input type="hidden" name="sub_office_id" value="{{ $subOffice?->id }}">
                        <button type="submit" class="join-glow-btn w-100" {{ $canJoin ? '' : 'disabled' }}>
                            {{ $canJoin ? 'Join Queue Now' : 'Queue Inactive' }}
                        </button>
                    </form>

                    @if(session('joined_token'))
                        <a href="{{ route('queue.live') }}" class="btn btn-outline-primary btn-lg w-100 mt-3 join-glow-btn" style="background: transparent; color: #0f62fe; border: 2px solid #0f62fe; box-shadow: none; animation: none;">
                            Track My Token
                        </a>
                    @endif

                    <div class="small text-muted mt-3">
                        After joining, you can track your queue token live using the button above.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
