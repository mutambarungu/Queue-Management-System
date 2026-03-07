@extends('layouts.app')
@section('title', 'Service Requests')
@section('content')

<div class="container-fluid">
    <div class="nk-content-inner">
        <div class="nk-content-body">

            <div class="nk-block nk-block-lg">
                <div class="nk-block-head">
                    <div class="nk-block-head-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="nk-block-title">All Student Service Requests</h4>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-inner">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="Submitted" {{ request('status')=='Submitted' ? 'selected' : '' }}>Submitted</option>
                                    <option value="In Review" {{ request('status')=='In Review' ? 'selected' : '' }}>In Review</option>
                                    <option value="Awaiting Student Response" {{ request('status')=='Awaiting Student Response' ? 'selected' : '' }}>Awaiting Student Response</option>
                                    <option value="Appointment Scheduled" {{ request('status')=='Appointment Scheduled' ? 'selected' : '' }}>Appointment Scheduled</option>
                                    <option value="Resolved" {{ request('status')=='Resolved' ? 'selected' : '' }}>Resolved</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </form>

                        <table class="datatable-init nowrap nk-tb-list nk-tb-ulist" data-auto-responsive="true">
                            <thead>
                                <tr class="nk-tb-item nk-tb-head">
                                    <th class="nk-tb-col">#</th>
                                    <th class="nk-tb-col">Request No</th>
                                    <th class="nk-tb-col">Student</th>
                                    <th class="nk-tb-col">Office</th>
                                    <th class="nk-tb-col">Service Type</th>
                                    <th class="nk-tb-col">Status</th>
                                    <th class="nk-tb-col">Waiting Time</th>
                                    <th class="nk-tb-col">Submitted On</th>
                                    <th class="nk-tb-col nk-tb-col-tools text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $index => $req)
                                <tr class="nk-tb-item">
                                    <td class="nk-tb-col">{{ $index + 1 }}</td>
                                    <td class="nk-tb-col">{{ $req->request_number }}</td>
                                    <td class="nk-tb-col">{{ $req->student->user->name ?? 'N/A' }}</td>
                                    <td class="nk-tb-col">{{ $req->office->name ?? 'N/A' }}</td>
                                    <td class="nk-tb-col">{{ $req->serviceType->name ?? 'N/A' }}</td>
                                    <td class="nk-tb-col">
                                        @php
                                        $badge = match($req->status){
                                        'Submitted' => 'primary',
                                        'In Review' => 'warning',
                                        'Awaiting Student Response' => 'info',
                                        'Appointment Required' => 'secondary',
                                        'Resolved' => 'success',
                                        'Closed' => 'dark',
                                        default => 'secondary'
                                        };
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ $req->status }}</span>
                                    </td>
                                    <td class="nk-tb-col">{{ $req->waiting_time }}</td>
                                    <td class="nk-tb-col">{{ $req->created_at->format('d M Y h:i A') }}</td>
                                    <td class="nk-tb-col">
                                        <a href="{{ route('staff.requests.show', $req->id) }}" class="btn btn-sm btn-outline-dark">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No service requests found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
