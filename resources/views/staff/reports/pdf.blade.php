<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Staff Queue Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h2 { margin: 0 0 6px; }
        p { margin: 0 0 12px; color: #374151; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h2>Staff Queue Report</h2>
    <p>
        Office: {{ optional($staff->office)->name ?? 'N/A' }} |
        Generated: {{ $generatedAt->format('Y-m-d H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Token</th>
                <th>Student ID</th>
                <th>Service</th>
                <th>Mode</th>
                <th>Status</th>
                <th>Queue Stage</th>
                <th>Queued At</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->token_code }}</td>
                    <td>{{ $row->student_id ?? 'Guest' }}</td>
                    <td>{{ optional($row->serviceType)->name ?? 'N/A' }}</td>
                    <td>{{ strtoupper((string) $row->request_mode) }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ strtoupper(str_replace('_', ' ', (string) $row->queue_stage)) }}</td>
                    <td>{{ optional($row->queued_at)->format('Y-m-d H:i') ?? optional($row->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ optional($row->updated_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
