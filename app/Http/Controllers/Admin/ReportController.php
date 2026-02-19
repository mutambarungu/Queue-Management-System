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
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reportType = $request->input('report_type', 'office');
        $offices = Office::all();
        $serviceTypes = ServiceType::all();
        $staffMembers = Staff::with('office')->orderBy('name')->get();

        if ($reportType === 'staff') {
            $staffPerformance = $this->staffPerformanceData($request);

            return view('admin.reports.index', [
                'reportType' => $reportType,
                'staffPerformance' => $staffPerformance,
                'requests' => collect(),
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
            'offices' => $offices,
            'serviceTypes' => $serviceTypes,
            'staffMembers' => $staffMembers,
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $reportType = $request->input('report_type', 'office');
        $data = $reportType === 'staff'
            ? $this->staffPerformanceData($request)
            : $this->filteredData($request);

        $pdf = Pdf::loadView('admin.reports.pdf', [
            'data' => $data,
            'reportType' => $reportType,
        ]);

        $filename = $reportType === 'staff'
            ? 'staff-performance-report.pdf'
            : 'service-requests-report.pdf';

        return $pdf->download($filename);
    }

    public function downloadExcel(Request $request)
{
    return Excel::download(
        new ServiceRequestsExport($request),
        'service-requests-report.xlsx',
        ExcelWriter::XLSX
    );
}

public function downloadCsv(Request $request)
{
    return Excel::download(
        new ServiceRequestsExport($request),
        'service-requests-report.csv',
        ExcelWriter::CSV
    );
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
}
