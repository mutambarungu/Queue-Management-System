@extends('layouts.app')

@section('title', 'Appointment Details')

@section('content')

@php
$status = strtolower($appointment->serviceRequest->status);

$statusClass = match ($status) {
    'approved' => 'bg-success',
    'pending' => 'bg-warning text-dark',
    'cancelled' => 'bg-danger',
    default => 'bg-secondary',
};
$staffNote = $appointment->serviceRequest->replies
    ->first(fn ($reply) => in_array($reply->user->role ?? null, ['staff', 'admin'], true));
@endphp

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">
            <i class="bi bi-calendar-check text-primary me-2"></i>
            Appointment Details
        </h4>

        <a href="{{ route('student.appointments.index') }}"
            class="btn btn-secondary p-3">
            <i class="bi bi-arrow-left"></i> Back to Appointments
        </a>
    </div>

    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body p-4">
            <div class="row g-4">

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body">
                            <h6 class="text-primary fw-semibold mb-3">
                                <i class="bi bi-clock-history me-2"></i>
                                Appointment Info
                            </h6>

                            <p class="mb-1"><small class="text-muted">Date</small></p>
                            <p class="fw-medium">{{ $appointment->appointment_date }}</p>

                            <p class="mb-1"><small class="text-muted">Time</small></p>
                            <p class="fw-medium">{{ $appointment->appointment_time }}</p>

                            <p class="mb-1"><small class="text-muted">Location</small></p>
                            <p class="fw-medium">{{ $appointment->location ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body">
                            <h6 class="text-primary fw-semibold mb-3">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Request Info
                            </h6>

                            <p class="mb-1"><small class="text-muted">Request ID</small></p>
                            <p class="fw-medium">{{ $appointment->serviceRequest->request_number }}</p>

                            <p class="mb-1"><small class="text-muted">Status</small></p>
                            <span class="badge rounded-pill px-3 py-2 {{ $statusClass }}">
                                {{ ucfirst($status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100 p-4">
                        <div class="card-body">
                            <h6 class="text-primary fw-semibold mb-3">
                                <i class="bi bi-person-circle me-2"></i>
                                Student Information
                            </h6>

                            <div class="d-flex align-items-center gap-3 mt-3">
                                <img src="{{ $appointment->serviceRequest->student->avatar
                                    ?? 'https://ui-avatars.com/api/?name=' .
                                    urlencode($appointment->serviceRequest->student->user->name) .
                                    '&background=0D6EFD&color=fff' }}"
                                    class="rounded-circle"
                                    width="60" height="60"
                                    alt="Student Avatar">

                                <div>
                                    <h6 class="mb-1 fw-semibold">
                                        {{ $appointment->serviceRequest->student->user->name }}
                                    </h6>
                                    <small class="text-muted">
                                        {{ $appointment->serviceRequest->student->user->email }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100 p-4">
                        <div class="card-body">
                            <h6 class="text-primary fw-semibold mb-3">
                                <i class="bi bi-person-badge me-2"></i>
                                Staff Information
                            </h6>

                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $appointment->staff->avatar
                                    ?? 'https://ui-avatars.com/api/?name=' .
                                    urlencode($appointment->staff->user->name) .
                                    '&background=198754&color=fff' }}"
                                    class="rounded-circle"
                                    width="60" height="60"
                                    alt="Staff Avatar">

                                <div>
                                    <h6 class="mb-1 fw-semibold">
                                        {{ $appointment->staff->user->name }}
                                    </h6>
                                    <small class="text-muted">
                                        {{ $appointment->staff->office->name ?? 'N/A' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body">
                            <h6 class="text-primary fw-semibold mb-3">
                                <i class="bi bi-chat-left-text me-2"></i>
                                Staff Note
                            </h6>
                            <p class="mb-0 text-muted">
                                @if($staffNote)
                                    {!! nl2br(e($staffNote->message)) !!}
                                @else
                                    No staff note provided yet.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast show align-items-center text-bg-success border-0">
        <div class="d-flex">
            <div class="toast-body">
                {{ session('success') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endif

@endsection
