@extends('layouts.app')
@section('title', 'My Service Requests')
@section('content')

<div class="container-fluid">
    <div class="nk-content-inner">
        <div class="nk-content-body">

            <div class="nk-block nk-block-lg">
                <div class="nk-block-head">
                    <div class="nk-block-head-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="nk-block-title">My Service Requests</h4>
                            <div class="d-flex gap-2">
                                @if(session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                <a href="{{ route('student.requests.create') }}" class="btn btn-primary p-3">
                                    + New Request
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        <table class="datatable-init nowrap nk-tb-list nk-tb-ulist" data-auto-responsive="true">
                            <thead>
                                <tr class="nk-tb-item nk-tb-head">
                                    <th class="nk-tb-col">#</th>
                                    <th class="nk-tb-col">Token</th>
                                    <th class="nk-tb-col">Request No</th>
                                    <th class="nk-tb-col">Office</th>
                                    <th class="nk-tb-col">Service Type</th>
                                    <th class="nk-tb-col">Status</th>
                                    <th class="nk-tb-col">Queue Stage</th>
                                    <th class="nk-tb-col">Submitted On</th>
                                    <th class="nk-tb-col">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($requests as $index => $req)
                                <tr class="nk-tb-item">
                                    <td class="nk-tb-col">{{ $index + 1 }}</td>
                                    <td class="nk-tb-col fw-bold">{{ $req->token_code }}</td>

                                    <td class="nk-tb-col fw-bold">
                                        {{ $req->request_number }}
                                    </td>

                                    <td class="nk-tb-col">
                                        {{ $req->office?->name ?? 'N/A' }}
                                    </td>

                                    <td class="nk-tb-col">
                                        {{ $req->serviceType?->name ?? 'N/A' }}
                                    </td>

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

                                        <span class="badge bg-{{ $badge }}">
                                            {{ $req->status }}
                                        </span>
                                    </td>

                                    <td class="nk-tb-col">
                                        <span class="badge bg-light text-dark">
                                            {{ strtoupper(str_replace('_', ' ', (string) ($req->queue_stage ?? 'waiting'))) }}
                                        </span>
                                    </td>

                                    <td class="nk-tb-col">{{ $req->created_at->format('d M Y h:i A') }}</td>

                                    <td class="nk-tb-col">
                                        <a href="{{ route('student.requests.show',$req->id) }}" class="btn btn-sm btn-outline-dark">
                                            View
                                        </a>
                                        @if(
                                            in_array($req->status, ['Submitted', 'In Review', 'Awaiting Student Response', 'Appointment Scheduled'], true)
                                            && !in_array((string) $req->queue_stage, ['completed', 'no_show'], true)
                                        )
                                            <a href="{{ route('student.requests.track-queue', $req->id) }}" class="btn btn-sm btn-outline-primary">
                                                Track Live Queue
                                            </a>
                                        @endif
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
@endsection
