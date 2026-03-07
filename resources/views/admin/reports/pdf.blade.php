@if(($reportType ?? 'office') === 'staff')
<h3>Staff Performance Report</h3>
<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Staff Name</th>
            <th>Staff ID</th>
            <th>Office</th>
            <th>Total Assigned</th>
            <th>Resolved</th>
            <th>Closed</th>
            <th>Pending</th>
            <th>Avg Resolution (hrs)</th>
            <th>Completion Rate (%)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $row)
        <tr>
            <td>{{ $row['staff_name'] }}</td>
            <td>{{ $row['staff_number'] }}</td>
            <td>{{ $row['office_name'] }}</td>
            <td>{{ $row['total_assigned'] }}</td>
            <td>{{ $row['resolved'] }}</td>
            <td>{{ $row['closed'] }}</td>
            <td>{{ $row['pending'] }}</td>
            <td>{{ $row['avg_resolution_hours'] ?? 'N/A' }}</td>
            <td>{{ $row['completion_rate'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@elseif(($reportType ?? 'office') === 'queue')
<h3>Queue Operations Report</h3>
<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Token</th>
            <th>Student ID</th>
            <th>Office</th>
            <th>Service</th>
            <th>Mode</th>
            <th>Status</th>
            <th>Queue Stage</th>
            <th>Queued At</th>
            <th>Updated</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $r)
        <tr>
            <td>{{ $r->token_code }}</td>
            <td>{{ $r->student_id ?? 'Guest' }}</td>
            <td>{{ optional($r->office)->name ?? 'N/A' }}</td>
            <td>{{ optional($r->serviceType)->name ?? 'N/A' }}</td>
            <td>{{ strtoupper((string) $r->request_mode) }}</td>
            <td>{{ $r->status }}</td>
            <td>{{ strtoupper(str_replace('_', ' ', (string) $r->queue_stage)) }}</td>
            <td>{{ optional($r->queued_at)->format('Y-m-d H:i') ?? optional($r->created_at)->format('Y-m-d H:i') }}</td>
            <td>{{ optional($r->updated_at)->format('Y-m-d H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<h3>Service Requests Report</h3>
<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Student</th>
            <th>Office</th>
            <th>Service</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $r)
        <tr>
            <td>{{ $r->student->name ?? 'N/A' }}</td>
            <td>{{ $r->office->name }}</td>
            <td>{{ $r->serviceType->name }}</td>
            <td>{{ $r->status }}</td>
            <td>{{ $r->created_at->format('Y-m-d') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
