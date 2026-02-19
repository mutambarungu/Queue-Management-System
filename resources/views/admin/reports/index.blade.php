@extends('layouts.app')
@section('title', 'Service Requests Report')
@section('content')

<div class="container">
    <h4 class="mb-4">Service Requests Report</h4>

    <form class="row g-3 mb-4">
        <div class="col-md-3">
            <select name="report_type" id="report_type" class="form-select">
                <option value="office" {{ request('report_type', 'office') === 'office' ? 'selected' : '' }}>Office Report</option>
                <option value="staff" {{ request('report_type') === 'staff' ? 'selected' : '' }}>Staff Performance Report</option>
            </select>
        </div>

        <div class="col-md-3">
            <select name="office_id" class="form-select">
                <option value="">All Offices</option>
                @foreach($offices as $office)
                <option value="{{ $office->id }}" {{ request('office_id') == $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3" id="status_filter_group">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                @foreach(['Submitted', 'In Review', 'Awaiting Student Response', 'Appointment Scheduled', 'Resolved', 'Closed'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-none" id="staff_filter_group">
            <select name="staff_number" class="form-select">
                <option value="">All Staff</option>
                @foreach($staffMembers as $staff)
                <option value="{{ $staff->staff_number }}" {{ request('staff_number') === $staff->staff_number ? 'selected' : '' }}>
                    {{ $staff->name }} ({{ $staff->staff_number }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
        </div>

        <div class="col-md-2">
            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
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

    @if(($reportType ?? request('report_type', 'office')) === 'staff')
    <table class="datatable-init nowrap nk-tb-list nk-tb-ulist" data-auto-responsive="true">
        <thead>
            <tr class="nk-tb-item nk-tb-head">
                <th class="nk-tb-col">#</th>
                <th class="nk-tb-col">Staff Name</th>
                <th class="nk-tb-col">Staff ID</th>
                <th class="nk-tb-col">Office</th>
                <th class="nk-tb-col">Total Assigned</th>
                <th class="nk-tb-col">Resolved</th>
                <th class="nk-tb-col">Closed</th>
                <th class="nk-tb-col">Pending</th>
                <th class="nk-tb-col">Avg Resolution (hrs)</th>
                <th class="nk-tb-col">Completion Rate (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($staffPerformance as $index => $row)
            <tr class="nk-tb-item">
                <td class="nk-tb-col">{{ $index + 1 }}</td>
                <td class="nk-tb-col">{{ $row['staff_name'] }}</td>
                <td class="nk-tb-col">{{ $row['staff_number'] }}</td>
                <td class="nk-tb-col">{{ $row['office_name'] }}</td>
                <td class="nk-tb-col">{{ $row['total_assigned'] }}</td>
                <td class="nk-tb-col">{{ $row['resolved'] }}</td>
                <td class="nk-tb-col">{{ $row['closed'] }}</td>
                <td class="nk-tb-col">{{ $row['pending'] }}</td>
                <td class="nk-tb-col">{{ $row['avg_resolution_hours'] ?? 'N/A' }}</td>
                <td class="nk-tb-col">{{ $row['completion_rate'] }}</td>
            </tr>
            @empty
            <tr class="nk-tb-item">
                <td class="nk-tb-col text-center" colspan="10">No staff performance data found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @else
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
    @endif
</div>
<script>
    function toggleReportFilters() {
        const reportType = document.getElementById('report_type').value;
        const statusFilter = document.getElementById('status_filter_group');
        const staffFilter = document.getElementById('staff_filter_group');

        if (reportType === 'staff') {
            statusFilter.classList.add('d-none');
            staffFilter.classList.remove('d-none');
        } else {
            statusFilter.classList.remove('d-none');
            staffFilter.classList.add('d-none');
        }
    }

    document.getElementById('report_type').addEventListener('change', toggleReportFilters);
    toggleReportFilters();
</script>

@endsection
