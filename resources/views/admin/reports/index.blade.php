@extends('layouts.app')
@section('title', 'Service Requests Report')
@section('content')

<div class="container">
    <h4 class="mb-4">Service Requests Report</h4>

    <form class="row g-3 mb-4">
        <div class="col-md-3">
            <select name="office_id" class="form-select">
                <option value="">All Offices</option>
                @foreach($offices as $office)
                <option value="{{ $office->id }}">{{ $office->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option>Submitted</option>
                <option>In Review</option>
                <option>Awaiting Student Response</option>
                <option>Appointment Scheduled</option>
                <option>Resolved</option>
                <option>Closed</option>
            </select>
        </div>

        <div class="col-md-2">
            <input type="date" name="from" class="form-control">
        </div>

        <div class="col-md-2">
            <input type="date" name="to" class="form-control">
        </div>

        <div class="col-md-2 d-grid">
            <button class="btn btn-primary">Filter</button>
        </div>
    </form>

    <div class="mb-3 d-flex gap-2" style="padding-bottom: 2rem;">
        <a href="{{ route('admin.reports.pdf', request()->query()) }}" class="btn btn-danger p-3">
            Download PDF
        </a>
        <a href="{{ route('admin.reports.excel', request()->query()) }}" class="btn btn-success p-3">
            Download Excel
        </a>
        <a href="{{ route('admin.reports.csv', request()->query()) }}" class="btn btn-secondary p-3">
            Download CSV
        </a>
    </div>

    <table class="datatable-init nowrap nk-tb-list nk-tb-ulist" data-auto-responsive="true">
        <thead>
            <tr class="nk-tb-item nk-tb-head">
                <th class="nk-tb-col">#</th>
                <th class="nk-tb-col">Student ID</th>
                <th class="nk-tb-col">Office</th>
                <th class="nk-tb-col">Service</th>
                <th class="nk-tb-col">Status</th>
                <th class="nk-tb-col">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $key => $req)
            <tr class="nk-tb-item">
                <td class="nk-tb-col">{{ $key + 1 }}</td>
                <td class="nk-tb-col">
                    <span title="{{ $req->student->name ?? 'No profile name' }}">
                        {{ $req->student->student_number ?? 'N/A' }}
                    </span>
                </td>
                <td class="nk-tb-col">{{ $req->office->name }}</td>
                <td class="nk-tb-col">{{ $req->serviceType->name }}</td>
                <td class="nk-tb-col">{{ $req->status }}</td>
                <td class="nk-tb-col">{{ $req->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
