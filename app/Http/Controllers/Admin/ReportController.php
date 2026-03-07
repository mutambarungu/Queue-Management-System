<?php

namespace App\Http\Controllers\Admin;

use Maatwebsite\Excel\Excel as ExcelWriter;
use App\Exports\ServiceRequestsExport;
use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use App\Models\ServiceType;
use App\Models\Staff;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reportType = $request->input('report_type', 'office');
        $offices = Office::all();
        $serviceTypes = ServiceType::query()
            ->when($request->filled('office_id'), fn ($query) => $query->where('office_id', (int) $request->office_id))
            ->orderBy('name')
            ->get()
            ->unique(fn (ServiceType $serviceType) => ServiceType::normalizeName($serviceType->name))
            ->values();
        $staffMembers = Staff::with('office')->orderBy('name')->get();

        if ($reportType === 'staff') {
            $staffPerformance = $this->staffPerformanceData($request);

            return view('admin.reports.index', [
                'reportType' => $reportType,
                'staffPerformance' => $staffPerformance,
                'requests' => collect(),
                'queueRequests' => collect(),
                'queueSummary' => [],
                'queueCharts' => [],
                'offices' => $offices,
                'serviceTypes' => $serviceTypes,
                'staffMembers' => $staffMembers,
            ]);
        }

        if ($reportType === 'queue') {
            $queueQuery = $this->queueFilteredQuery($request)->with(['office', 'serviceType.subOffice', 'student']);
            $queueRequests = (clone $queueQuery)->latest('created_at')->paginate(20)->withQueryString();
            $queueSummary = $this->queueSummaryData(clone $queueQuery);
            $queueCharts = $this->queueChartData($request, clone $queueQuery);

            return view('admin.reports.index', [
                'reportType' => $reportType,
                'staffPerformance' => collect(),
                'requests' => collect(),
                'queueRequests' => $queueRequests,
                'queueSummary' => $queueSummary,
                'queueCharts' => $queueCharts,
                'offices' => $offices,
                'serviceTypes' => $serviceTypes,
                'staffMembers' => $staffMembers,
            ]);
        }

        $query = ServiceRequest::with(['office', 'serviceType', 'student']);

        if ($request->office_id) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->service_type_id) {
            $query->where('service_type_id', $request->service_type_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from && $request->to) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        $requests = $query->latest()->paginate(20);

        return view('admin.reports.index', [
            'reportType' => $reportType,
            'staffPerformance' => collect(),
            'requests' => $requests,
            'queueRequests' => collect(),
            'queueSummary' => [],
            'queueCharts' => [],
            'offices' => $offices,
            'serviceTypes' => $serviceTypes,
            'staffMembers' => $staffMembers,
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $reportType = $request->input('report_type', 'office');
        $data = match ($reportType) {
            'staff' => $this->staffPerformanceData($request),
            'queue' => $this->queueFilteredQuery($request)->with(['office', 'serviceType.subOffice', 'student'])->get(),
            default => $this->filteredData($request),
        };

        $pdf = Pdf::loadView('admin.reports.pdf', [
            'data' => $data,
            'reportType' => $reportType,
        ]);

        $filename = match ($reportType) {
            'staff' => 'staff-performance-report.pdf',
            'queue' => 'queue-operations-report.pdf',
            default => 'service-requests-report.pdf',
        };

        return $pdf->download($filename);
    }

    public function downloadExcel(Request $request)
    {
        $reportType = $request->input('report_type', 'office');
        $filename = match ($reportType) {
            'staff' => 'staff-performance-report.xlsx',
            'queue' => 'queue-operations-report.xlsx',
            default => 'service-requests-report.xlsx',
        };

        return Excel::download(new ServiceRequestsExport($request), $filename, ExcelWriter::XLSX);
    }

    public function downloadCsv(Request $request)
    {
        $reportType = $request->input('report_type', 'office');
        $filename = match ($reportType) {
            'staff' => 'staff-performance-report.csv',
            'queue' => 'queue-operations-report.csv',
            default => 'service-requests-report.csv',
        };

        return Excel::download(new ServiceRequestsExport($request), $filename, ExcelWriter::CSV);
    }

    private function filteredData(Request $request)
    {
        return ServiceRequest::with(['office', 'serviceType', 'student'])
            ->when($request->office_id, fn($q) => $q->where('office_id', $request->office_id))
            ->when($request->service_type_id, fn($q) => $q->where('service_type_id', $request->service_type_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->from && $request->to, fn($q) =>
                $q->whereBetween('created_at', [$request->from, $request->to])
            )->get();
    }

    private function staffPerformanceData(Request $request)
    {
        $start = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $end = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;

        $staffQuery = Staff::with('office')
            ->when($request->filled('staff_number'), fn($q) => $q->where('staff_number', $request->staff_number))
            ->when($request->filled('office_id'), fn($q) => $q->where('office_id', $request->office_id));

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

            return [
                'staff_name' => $staff->name ?? 'N/A',
                'staff_number' => $staff->staff_number,
                'office_name' => $staff->office?->name ?? 'N/A',
                'total_assigned' => $totalHandled,
                'resolved' => $resolvedCount,
                'closed' => $closedCount,
                'pending' => $pendingCount,
                'avg_resolution_hours' => $avgResolutionHours,
                'completion_rate' => $completionRate,
            ];
        })->values();
    }

    private function queueFilteredQuery(Request $request)
    {
        return ServiceRequest::query()
            ->whereNull('archived_at')
            ->when($request->filled('office_id'), fn ($query) => $query->where('office_id', (int) $request->office_id))
            ->when($request->filled('service_type_id'), fn ($query) => $query->where('service_type_id', (int) $request->service_type_id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('request_mode'), fn ($query) => $query->where('request_mode', $request->request_mode))
            ->when($request->filled('queue_stage'), fn ($query) => $query->where('queue_stage', $request->queue_stage))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->to));
    }

    private function queueSummaryData($query): array
    {
        $total = (clone $query)->count();
        $stageCounts = (clone $query)
            ->selectRaw('queue_stage, COUNT(*) as aggregate_count')
            ->groupBy('queue_stage')
            ->pluck('aggregate_count', 'queue_stage');

        return [
            'total' => $total,
            'waiting' => (int) ($stageCounts['waiting'] ?? 0),
            'called' => (int) ($stageCounts['called'] ?? 0),
            'serving' => (int) ($stageCounts['serving'] ?? 0),
            'completed' => (int) ($stageCounts['completed'] ?? 0),
            'no_show' => (int) ($stageCounts['no_show'] ?? 0),
        ];
    }

    private function queueChartData(Request $request, $query): array
    {
        $fixedStages = ['waiting', 'called', 'serving', 'completed', 'no_show'];
        $stageCountsRaw = (clone $query)
            ->selectRaw('queue_stage, COUNT(*) as aggregate_count')
            ->groupBy('queue_stage')
            ->pluck('aggregate_count', 'queue_stage');

        $modeCountsRaw = (clone $query)
            ->selectRaw('request_mode, COUNT(*) as aggregate_count')
            ->groupBy('request_mode')
            ->pluck('aggregate_count', 'request_mode');

        [$startDate, $endDate] = $this->resolveTrendRange($request);
        $trendRows = (clone $query)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(*) as joined_count')
            ->selectRaw("SUM(CASE WHEN queue_stage = 'completed' THEN 1 ELSE 0 END) as served_count")
            ->selectRaw("SUM(CASE WHEN queue_stage = 'no_show' THEN 1 ELSE 0 END) as no_show_count")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $joined = [];
        $served = [];
        $noShow = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $day = $date->format('Y-m-d');
            $row = $trendRows->get($day);
            $labels[] = $date->format('M d');
            $joined[] = (int) ($row->joined_count ?? 0);
            $served[] = (int) ($row->served_count ?? 0);
            $noShow[] = (int) ($row->no_show_count ?? 0);
        }

        return [
            'stage' => [
                'labels' => collect($fixedStages)->map(fn ($stage) => strtoupper(str_replace('_', ' ', $stage)))->values()->all(),
                'counts' => collect($fixedStages)->map(fn ($stage) => (int) ($stageCountsRaw[$stage] ?? 0))->values()->all(),
            ],
            'mode' => [
                'labels' => $modeCountsRaw->keys()->map(fn ($mode) => strtoupper(str_replace('_', ' ', (string) $mode)))->values()->all(),
                'counts' => $modeCountsRaw->values()->map(fn ($count) => (int) $count)->values()->all(),
            ],
            'trend' => [
                'labels' => $labels,
                'joined' => $joined,
                'served' => $served,
                'no_show' => $noShow,
            ],
        ];
    }

    private function resolveTrendRange(Request $request): array
    {
        $start = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->subDays(6)->startOfDay();
        $end = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }
}
