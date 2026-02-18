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
                        </div>
                    </div>
                </div>
                <!-- Validation Errors -->
                @if ($errors->any())
                <div class="alert alert-danger small">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <div class="card shadow-sm">
                    <div class="card-inner">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="Submitted" {{ request('status')=='Submitted' ? 'selected' : '' }}>Submitted</option>
                                    <option value="In Review" {{ request('status')=='In Review' ? 'selected' : '' }}>In Review</option>
                                    <option value="Resolved" {{ request('status')=='Resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="Closed" {{ request('status')=='Closed' ? 'selected' : '' }}>Closed</option>
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
                                    <th class="nk-tb-col">Priority</th>
                                    <th class="nk-tb-col">Waiting Time</th>
                                    <th class="nk-tb-col">Submitted On</th>
                                    <th class="nk-tb-col nk-tb-col-tools text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $index => $req)
                                <tr class="nk-tb-item">
                                    <td class="nk-tb-col">{{ $index + 1 + ($requests->currentPage()-1)*$requests->perPage() }}</td>
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
                                    <td class="nk-tb-col">
                                        <span class="badge bg-{{ $req->priority === 'urgent' ? 'danger' : 'secondary' }}">
                                            {{ ucfirst($req->priority) }}
                                        </span>
                                    </td>
                                    <td class="nk-tb-col">{{ $req->waiting_time }}</td>
                                    <td class="nk-tb-col">{{ $req->created_at->format('d M Y h:i A') }}</td>
                                    <td class="nk-tb-col">
                                        <a href="{{ route('admin.requests.show', $req->id) }}" class="btn btn-sm btn-outline-dark">
                                            View
                                        </a>
                                        @if(in_array($req->status, ['Resolved', 'Closed']))
                                        <button class="btn btn-sm btn-warning"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveModal{{ $req->id }}">
                                            <i class="bi bi-archive"></i> Archive
                                        </button>

                                        @endif
                                        <div class="modal fade" id="archiveModal{{ $req->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title text-warning">
                                                            <i class="bi bi-exclamation-triangle"></i> Confirm Archive
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <p>
                                                            Are you sure you want to archive this request?
                                                        </p>
                                                        <p class="text-muted mb-0">
                                                            <strong>Request ID:</strong> {{ $req->request_number }}
                                                        </p>
                                                        <small class="text-danger">
                                                            This will remove it from active requests.
                                                        </small>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            Cancel
                                                        </button>

                                                        <form action="{{ route('admin.requests.archive', $req->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="bi bi-archive"></i> Yes, Archive
                                                            </button>
                                                        </form>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No service requests found.</td>
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
