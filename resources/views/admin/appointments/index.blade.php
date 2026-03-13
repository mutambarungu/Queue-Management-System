@extends('layouts.app')

@section('title', 'Appointments')

@section('content')

<div class="container-fluid">
    <div class="nk-content-inner">
        <div class="nk-content-body">

            <div class="nk-block nk-block-lg">
                <div class="nk-block-head">
                    <div class="nk-block-head-content">
                        <h4 class="nk-block-title">Appointments List</h4>
                        <div class="nk-block-des">
                            <p>Manage all appointments scheduled by students.</p>
                        </div>
                    </div>
                </div>

                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        <table class="datatable-init nowrap nk-tb-list nk-tb-ulist"
                            data-auto-responsive="true">
                            <thead>
                                <tr class="nk-tb-item nk-tb-head">
                                    <th class="nk-tb-col">#</th>
                                    <th class="nk-tb-col">Request</th>
                                    <th class="nk-tb-col">Student</th>
                                    <th class="nk-tb-col">Staff</th>
                                    <th class="nk-tb-col">Date</th>
                                    <th class="nk-tb-col">Time</th>
                                    <th class="nk-tb-col">Office</th>
                                    <th class="nk-tb-col">Status</th>
                                    <th class="nk-tb-col nk-tb-col-tools text-end">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($appointments as $index => $appointment)
                                <tr class="nk-tb-item">
                                    <td class="nk-tb-col">{{ $index + 1 + ($appointments->currentPage()-1)*$appointments->perPage() }}</td>

                                    <td class="nk-tb-col">
                                        <span class="badge bg-primary">
                                            {{ $appointment->serviceRequest?->request_number ?? 'N/A' }}
                                        </span>
                                    </td>

                                    <td class="nk-tb-col">{{ $appointment->serviceRequest?->student?->user?->name ?? 'N/A' }}</td>
                                    <td class="nk-tb-col">{{ $appointment->staff?->user?->name ?? 'N/A' }}</td>
                                    <td class="nk-tb-col">{{ $appointment->appointment_date }}</td>
                                    <td class="nk-tb-col">{{ $appointment->appointment_time }}</td>
                                    <td class="nk-tb-col">{{ $appointment->staff?->office?->name ?? 'N/A' }}</td>
                                    <td class="nk-tb-col tb-col-md">
                                        <span class="tb-status text-{{ $appointment->status === 'completed' ? 'success' : ($appointment->status === 'cancelled' ? 'danger' : 'warning') }}">{{ $appointment->status }}</span>
                                    </td>
                                    <td class="nk-tb-col nk-tb-col-tools">
                                        <ul class="nk-tb-actions gx-1">
                                            <li>
                                                <div class="drodown">
                                                    <a href="#"
                                                        class="dropdown-toggle btn btn-icon btn-trigger"
                                                        data-bs-toggle="dropdown">
                                                        <em class="icon ni ni-more-h"></em>
                                                    </a>
                                                    <div
                                                        class="dropdown-menu dropdown-menu-end">
                                                        <ul class="link-list-opt no-bdr">
                                                            <li>
                                                                <a href="{{ route('admin.appointments.show', $appointment) }}">
                                                                    <em class="icon ni ni-eye"></em>
                                                                    <span>View Details</span>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="#"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#cancelModal{{ $appointment->id }}">
                                                                    <em class="icon ni ni-cross-circle"></em>
                                                                    <span>Cancel Appointment</span>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($appointments as $index => $appointment)
{{-- Cancel Modal --}}
<div class="modal fade" id="cancelModal{{ $appointment->id }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Cancel Appointment</h5>
            </div>

            <div class="modal-body">
                Are you sure you want to cancel this appointment?
            </div>

            <div class="modal-footer">
                <form method="POST"
                    action="{{ route('admin.appointments.cancel', $appointment) }}">
                    @csrf
                    @method('PATCH')

                    <button class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                    <button class="btn btn-danger">Yes, Cancel</button>
                </form>
            </div>

        </div>
    </div>
</div>
@endforeach

@endsection
