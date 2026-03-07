<?php

namespace App\Exports;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ServiceRequestsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithCustomCsvSettings
{
    protected Request $request;
    protected string $reportType;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->reportType = $request->input('report_type', 'office');
    }

    public function collection()
    {
        if ($this->reportType === 'staff') {
            return $this->staffPerformanceCollection();
        }

        if ($this->reportType === 'queue') {
            return $this->queueCollection();
        }

        return ServiceRequest::with(['office', 'serviceType', 'student.user'])
            ->when($this->request->office_id, fn ($q) =>
                $q->where('office_id', $this->request->office_id)
            )
            ->when($this->request->service_type_id, fn ($q) =>
                $q->where('service_type_id', $this->request->service_type_id)
            )
            ->when($this->request->status, fn ($q) =>
                $q->where('status', $this->request->status)
            )
            ->when($this->request->from && $this->request->to, fn ($q) =>
                $q->whereBetween('created_at', [$this->request->from, $this->request->to])
            )
            ->get()
      ->map(function ($r) {

    $studentName = $r->student?->name ?? 'Unknown Student';

    return [
        $studentName,
        $r->office->name ?? '-',
        $r->serviceType->name ?? '-',
        $r->status,
        $r->created_at->format('Y-m-d'), // ✅ NO ##### in Excel
    ];
});
    }

    public function headings(): array
    {
        if ($this->reportType === 'staff') {
            return [
                'Staff Name',
                'Staff Number',
                'Office',
                'Total Assigned',
                'Resolved',
                'Closed',
                'Pending',
                'Avg Resolution Hours',
                'Completion Rate (%)',
            ];
        }

        if ($this->reportType === 'queue') {
            return [
                'Token',
                'Student ID',
                'Office',
                'Service',
                'Mode',
                'Status',
                'Queue Stage',
                'Queued At',
                'Updated',
            ];
        }

        return [
            'Student',
            'Office',
            'Service',
            'Status',
            'Date',
        ];
    }

    private function staffPerformanceCollection()
    {
        $start = $this->request->filled('from') ? Carbon::parse($this->request->from)->startOfDay() : null;
        $end = $this->request->filled('to') ? Carbon::parse($this->request->to)->endOfDay() : null;

        $staffQuery = Staff::with('office')
            ->when($this->request->filled('staff_number'), fn($q) => $q->where('staff_number', $this->request->staff_number))
            ->when($this->request->filled('office_id'), fn($q) => $q->where('office_id', $this->request->office_id));

        return $staffQuery->get()->map(function ($staff) use ($start, $end) {
            $handledIdsQuery = ServiceRequestReply::query()
                ->whereHas('user', fn($q) => $q->where('staff_number', $staff->staff_number))
                ->select('service_request_id')
                ->distinct();

            if ($start && $end) {
                $handledIdsQuery->whereBetween('created_at', [$start, $end]);
            }

            $handledIds = $handledIdsQuery->pluck('service_request_id');
            $handledRequests = ServiceRequest::query()->whereIn('id', $handledIds);

            $totalHandled = (clone $handledRequests)->count();
            $resolvedCount = (clone $handledRequests)->where('status', 'Resolved')->count();
            $closedCount = (clone $handledRequests)->where('status', 'Closed')->count();
            $completed = $resolvedCount + $closedCount;
            $pendingCount = max(0, $totalHandled - $completed);

            $avgSeconds = (clone $handledRequests)
                ->whereIn('status', ['Resolved', 'Closed'])
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) AS avg_seconds')
                ->value('avg_seconds');

            $avgResolutionHours = $avgSeconds ? round(((float) $avgSeconds) / 3600, 2) : null;
            $completionRate = $totalHandled > 0 ? round(($completed / $totalHandled) * 100, 2) : 0;

            return collect([
                $staff->name ?? 'N/A',
                $staff->staff_number,
                $staff->office?->name ?? 'N/A',
                $totalHandled,
                $resolvedCount,
                $closedCount,
                $pendingCount,
                $avgResolutionHours ?? 'N/A',
                $completionRate,
            ]);
        });
    }

    private function queueCollection()
    {
        return ServiceRequest::with(['office', 'serviceType', 'student'])
            ->whereNull('archived_at')
            ->when($this->request->filled('office_id'), fn ($q) => $q->where('office_id', (int) $this->request->office_id))
            ->when($this->request->filled('service_type_id'), fn ($q) => $q->where('service_type_id', (int) $this->request->service_type_id))
            ->when($this->request->filled('status'), fn ($q) => $q->where('status', $this->request->status))
            ->when($this->request->filled('request_mode'), fn ($q) => $q->where('request_mode', $this->request->request_mode))
            ->when($this->request->filled('queue_stage'), fn ($q) => $q->where('queue_stage', $this->request->queue_stage))
            ->when($this->request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $this->request->from))
            ->when($this->request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $this->request->to))
            ->latest('created_at')
            ->get()
            ->map(function (ServiceRequest $row) {
                return [
                    $row->token_code,
                    $row->student_id ?? 'Guest',
                    optional($row->office)->name ?? 'N/A',
                    optional($row->serviceType)->name ?? 'N/A',
                    strtoupper((string) $row->request_mode),
                    $row->status,
                    strtoupper(str_replace('_', ' ', (string) $row->queue_stage)),
                    optional($row->queued_at)?->format('Y-m-d H:i') ?? optional($row->created_at)?->format('Y-m-d H:i'),
                    optional($row->updated_at)?->format('Y-m-d H:i'),
                ];
            });
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '"',
            'line_ending' => PHP_EOL,
            'use_bom' => true, // Excel-safe
        ];
    }
}
