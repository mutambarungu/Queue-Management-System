@extends('layouts.app')

@section('title', 'Appointment Details')

@section('content')

@php
$serviceRequest = $appointment->serviceRequest;
$studentUser = $serviceRequest?->student?->user;
$staffUser = $appointment->staff?->user;
$studentName = $studentUser?->name ?? 'N/A';
$studentEmail = $studentUser?->email ?? 'N/A';
$staffName = $staffUser?->name ?? 'N/A';
$staffOffice = $appointment->staff?->office?->name ?? 'N/A';
$studentAvatar = $appointment->serviceRequest?->student?->avatar;
$staffAvatar = $appointment->staff?->avatar;
$studentAvatar = $studentAvatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($studentName) . '&background=0D6EFD&color=fff';
$staffAvatar = $staffAvatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($staffName) . '&background=198754&color=fff';
$status = strtolower($serviceRequest?->status ?? 'unknown');

$statusClass = match ($status) {
    'approved' => 'bg-success',
    'pending' => 'bg-warning text-dark',
    'cancelled' => 'bg-danger',
    default => 'bg-secondary',
};
@endphp

<div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">
            <i class="bi bi-calendar-check text-primary me-2"></i>
            Appointment Details
        </h4>

        <div class="d-flex gap-2">

            <button class="btn btn-warning p-3"
                data-bs-toggle="modal"
                data-bs-target="#rescheduleModal">
                <i class="bi bi-arrow-repeat"></i> Reschedule
            </button>

            <a href="{{ route('admin.appointments.index') }}"
                class="btn btn-secondary p-3">
                <i class="bi bi-arrow-left"></i> Back to Appointments
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body p-4">

            <div class="row g-4">

                <!-- Appointment Info -->
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

                <!-- Request Info -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body">
                            <h6 class="text-primary fw-semibold mb-3">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Request Info
                            </h6>

                            <p class="mb-1"><small class="text-muted">Request ID</small></p>
                            <p class="fw-medium">{{ $serviceRequest?->request_number ?? 'N/A' }}</p>

                            <p class="mb-1"><small class="text-muted">Status</small></p>
                            <span class="badge rounded-pill px-3 py-2 {{ $statusClass }}">
                                {{ ucfirst($status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Student -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100 p-4">
                        <div class="card-body">

                            <h6 class="text-primary fw-semibold mb-3">
                                <i class="bi bi-person-circle me-2"></i>
                                Student Information
                            </h6>

                            <div class="d-flex align-items-center gap-3 mt-3">
                                <img src="{{ $studentAvatar }}"
                                    class="rounded-circle"
                                    width="60" height="60"
                                    alt="Student Avatar">

                                <div>
                                    <h6 class="mb-1 fw-semibold">
                                        {{ $studentName }}
                                    </h6>
                                    <small class="text-muted">
                                        {{ $studentEmail }}
                                    </small>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>


                <!-- Staff -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100 p-4">
                        <div class="card-body">

                            <h6 class="text-primary fw-semibold mb-3">
                                <i class="bi bi-person-badge me-2"></i>
                                Staff Information
                            </h6>

                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $staffAvatar }}"
                                    class="rounded-circle"
                                    width="60" height="60"
                                    alt="Staff Avatar">

                                <div>
                                    <h6 class="mb-1 fw-semibold">
                                        {{ $staffName }}
                                    </h6>
                                    <small class="text-muted">
                                        {{ $staffOffice }}
                                    </small>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>


            </div>

        </div>
    </div>

</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">

            <form method="POST"
                action="{{ route('admin.appointments.reschedule', $appointment->id) }}">
                @csrf
                @method('PUT')

                <div class="modal-header bg-warning bg-opacity-10 border-0 rounded-top-4">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-calendar-event me-2"></i>
                        Reschedule Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">

                    <div class="mb-3">
                        <label class="form-label fw-medium">New Date</label>
                        <input type="date"
                            name="appointment_date"
                            class="form-control"
                            value="{{ $appointment->appointment_date }}"
                            min="{{ now()->toDateString() }}"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">New Time</label>
                        <input type="time"
                            name="appointment_time"
                            class="form-control"
                            value="{{ $appointment->appointment_time }}"
                            required>
                    </div>

                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-1"></i>
                        The student and staff will be notified after rescheduling.
                    </div>

                </div>

                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                        class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>
                        Update Schedule
                    </button>
                </div>

            </form>

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
