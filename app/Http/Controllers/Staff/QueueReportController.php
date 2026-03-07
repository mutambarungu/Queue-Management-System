<?php

namespace App\Http\Controllers\Staff;

use App\Exports\StaffQueueReportExport;
use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Staff;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class QueueReportController extends Controller
{
    public function index(Request $request)
    {
        $staff = $request->user()->staff;
        abort_unless($staff, 403);

        $serviceTypes = $this->laneServiceTypes($staff);
        $query = $this->filteredQuery($request, $staff)->with(['office', 'serviceType.subOffice', 'student']);

        $requests = (clone $query)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $summary = $this->summaryData(clone $query);
        $charts = $this->chartData($request, clone $query);

        return view('staff.reports.index', [
            'requests' => $requests,
            'serviceTypes' => $serviceTypes,
            'summary' => $summary,
            'charts' => $charts,
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $staff = $request->user()->staff;
        abort_unless($staff, 403);

        $data = $this->filteredQuery($request, $staff)
            ->with(['office', 'serviceType.subOffice', 'student'])
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('staff.reports.pdf', [
            'data' => $data,
            'staff' => $staff,
            'generatedAt' => now(),
        ]);

        return $pdf->download('staff-queue-report.pdf');
    }

    public function downloadExcel(Request $request)
    {
        $staff = $request->user()->staff;
        abort_unless($staff, 403);

        return Excel::download(
            new StaffQueueReportExport($request, $staff),
            'staff-queue-report.xlsx',
            ExcelWriter::XLSX
        );
    }

    public function downloadCsv(Request $request)
    {
        $staff = $request->user()->staff;
        abort_unless($staff, 403);

        return Excel::download(
            new StaffQueueReportExport($request, $staff),
            'staff-queue-report.csv',
            ExcelWriter::CSV
        );
    }

    private function filteredQuery(Request $request, Staff $staff): Builder
    {
        $query = $this->staffScopedRequests($staff)
            ->whereNull('archived_at');

        if ($request->filled('service_type_id')) {
            $query->where('service_type_id', (int) $request->service_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('request_mode')) {
            $query->where('request_mode', $request->request_mode);
        }

        if ($request->filled('queue_stage')) {
            $query->where('queue_stage', $request->queue_stage);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return $query;
    }

    private function summaryData(Builder $query): array
    {
        $total = (clone $query)->count();

        $stageCounts = (clone $query)
            ->selectRaw('queue_stage, COUNT(*) as aggregate_count')
            ->groupBy('queue_stage')
            ->pluck('aggregate_count', 'queue_stage');

        $avgWaitMinutes = (clone $query)
            ->whereNotNull('queued_at')
            ->whereNotNull('called_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, queued_at, called_at)) as avg_wait')
            ->value('avg_wait');

        $avgServiceMinutes = (clone $query)
            ->whereNotNull('called_at')
            ->whereIn('queue_stage', ['completed', 'no_show'])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, called_at, updated_at)) as avg_service')
            ->value('avg_service');

        return [
            'total' => $total,
            'waiting' => (int) ($stageCounts['waiting'] ?? 0),
            'called' => (int) ($stageCounts['called'] ?? 0),
            'serving' => (int) ($stageCounts['serving'] ?? 0),
            'completed' => (int) ($stageCounts['completed'] ?? 0),
            'no_show' => (int) ($stageCounts['no_show'] ?? 0),
            'avg_wait_minutes' => $avgWaitMinutes !== null ? round((float) $avgWaitMinutes, 1) : null,
            'avg_service_minutes' => $avgServiceMinutes !== null ? round((float) $avgServiceMinutes, 1) : null,
        ];
    }

    private function chartData(Request $request, Builder $query): array
    {
        $fixedStages = ['waiting', 'called', 'serving', 'completed', 'no_show'];

        $stageCountsRaw = (clone $query)
            ->selectRaw('queue_stage, COUNT(*) as aggregate_count')
            ->groupBy('queue_stage')
            ->pluck('aggregate_count', 'queue_stage');

        $stageCounts = collect($fixedStages)
            ->map(fn (string $stage) => (int) ($stageCountsRaw[$stage] ?? 0))
            ->values();

        $modeCountsRaw = (clone $query)
            ->selectRaw('request_mode, COUNT(*) as aggregate_count')
            ->groupBy('request_mode')
            ->pluck('aggregate_count', 'request_mode');

        $modeLabels = $modeCountsRaw->keys()
            ->map(fn (string $mode) => strtoupper(str_replace('_', ' ', $mode)))
            ->values();

        $modeCounts = $modeCountsRaw->values()->map(fn ($count) => (int) $count)->values();

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
                'counts' => $stageCounts->all(),
            ],
            'mode' => [
                'labels' => $modeLabels->all(),
                'counts' => $modeCounts->all(),
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

    private function laneServiceTypes(Staff $staff)
    {
        $serviceTypeIds = $this->staffScopedRequests($staff)
            ->select('service_type_id')
            ->distinct()
            ->pluck('service_type_id');

        return ServiceType::query()
            ->whereIn('id', $serviceTypeIds)
            ->orderBy('name')
            ->get()
            ->unique(fn (ServiceType $serviceType) => ServiceType::normalizeName($serviceType->name))
            ->values();
    }

    private function staffScopedRequests(Staff $staff): Builder
    {
        return ServiceRequest::query()
            ->where('office_id', $staff->office_id)
            ->whereHas('serviceType', function ($serviceTypeQuery) use ($staff) {
                if (filled($staff->sub_office_id)) {
                    $serviceTypeQuery->where('sub_office_id', $staff->sub_office_id);
                } else {
                    $serviceTypeQuery->whereNull('sub_office_id');
                }
            })
            ->where(function ($query) use ($staff) {
                $query->whereDoesntHave('student')
                    ->orWhereHas('student', function ($studentQuery) use ($staff) {
                        if (filled($staff->campus)) {
                            $studentQuery->where('campus', $staff->campus);
                        }

                        if (filled($staff->faculty)) {
                            $studentQuery->where('faculty', $staff->faculty);
                        }

                        if (filled($staff->department)) {
                            $studentQuery->where('department', $staff->department);
                        }
                    });
            });
    }
}
