<?php

namespace App\Exports;

use App\Models\ServiceRequest;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StaffQueueReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithCustomCsvSettings
{
    public function __construct(
        private readonly Request $request,
        private readonly Staff $staff
    ) {
    }

    public function collection()
    {
        return $this->filteredQuery()
            ->with(['office', 'serviceType.subOffice', 'student'])
            ->latest('created_at')
            ->get()
            ->map(function (ServiceRequest $request) {
                return [
                    $request->token_code,
                    $request->student_id ?? 'Guest',
                    optional($request->office)->name ?? 'N/A',
                    optional(optional($request->serviceType)->subOffice)->name ?? 'General Queue',
                    optional($request->serviceType)->name ?? 'N/A',
                    strtoupper((string) $request->request_mode),
                    $request->status,
                    strtoupper(str_replace('_', ' ', (string) $request->queue_stage)),
                    optional($request->queued_at)?->format('Y-m-d H:i'),
                    optional($request->called_at)?->format('Y-m-d H:i'),
                    optional($request->updated_at)?->format('Y-m-d H:i'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Token',
            'Student ID',
            'Office',
            'Lane',
            'Service',
            'Mode',
            'Status',
            'Queue Stage',
            'Queued At',
            'Called At',
            'Last Updated',
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '"',
            'line_ending' => PHP_EOL,
            'use_bom' => true,
        ];
    }

    private function filteredQuery(): Builder
    {
        $query = $this->staffScopedRequests()->whereNull('archived_at');

        if ($this->request->filled('service_type_id')) {
            $query->where('service_type_id', (int) $this->request->service_type_id);
        }

        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }

        if ($this->request->filled('request_mode')) {
            $query->where('request_mode', $this->request->request_mode);
        }

        if ($this->request->filled('queue_stage')) {
            $query->where('queue_stage', $this->request->queue_stage);
        }

        if ($this->request->filled('from')) {
            $query->whereDate('created_at', '>=', $this->request->from);
        }

        if ($this->request->filled('to')) {
            $query->whereDate('created_at', '<=', $this->request->to);
        }

        return $query;
    }

    private function staffScopedRequests(): Builder
    {
        $staff = $this->staff;

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
