<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\ServiceRequest;
use App\Support\QueueBusinessCalendar;

class QueueOperationsController extends Controller
{
    public function index()
    {
        $today = QueueBusinessCalendar::now()->toDateString();
        $officeIds = Office::query()->pluck('id');

        $nowServing = ServiceRequest::query()
            ->with(['office', 'serviceType.subOffice'])
            ->whereIn('office_id', $officeIds)
            ->whereIn('request_mode', ['walk_in', 'appointment'])
            ->whereNull('archived_at')
            ->where('queue_stage', 'serving')
            ->orderByRaw('COALESCE(called_at, queued_at, created_at)')
            ->limit(12)
            ->get();

        $laneStats = ServiceRequest::query()
            ->with(['office', 'serviceType.subOffice', 'appointment'])
            ->whereIn('office_id', $officeIds)
            ->whereIn('request_mode', ['walk_in', 'appointment'])
            ->whereNull('archived_at')
            ->where('queue_stage', 'waiting')
            ->get()
            ->groupBy(function (ServiceRequest $request) {
                return implode('|', [
                    $request->office_id,
                    optional($request->serviceType)->sub_office_id ?? 'general',
                ]);
            })
            ->map(function ($requests) use ($today) {
                $sample = $requests->first();
                $appointmentWaitingToday = $requests
                    ->where('request_mode', 'appointment')
                    ->filter(fn (ServiceRequest $request) => optional($request->appointment)->appointment_date === $today)
                    ->count();
                return [
                    'office' => optional($sample->office)->name ?? 'Unknown Office',
                    'sub_office' => optional(optional($sample->serviceType)->subOffice)->name ?? 'General Queue',
                    'walk_in_waiting' => $requests->where('request_mode', 'walk_in')->count(),
                    'appointment_waiting' => $appointmentWaitingToday,
                    'tv_url' => route('queue.public.display', $sample->office_id),
                ];
            })
            ->sortBy(['office', 'sub_office'])
            ->values();

        return view('admin.queue.operations', compact('nowServing', 'laneStats'));
    }

    public function status()
    {
        $today = QueueBusinessCalendar::now()->toDateString();
        $officeIds = Office::query()->pluck('id');

        $nowServing = ServiceRequest::query()
            ->with(['office', 'serviceType.subOffice'])
            ->whereIn('office_id', $officeIds)
            ->whereIn('request_mode', ['walk_in', 'appointment'])
            ->whereNull('archived_at')
            ->where('queue_stage', 'serving')
            ->orderByRaw('COALESCE(called_at, queued_at, created_at)')
            ->limit(12)
            ->get()
            ->map(fn (ServiceRequest $row) => [
                'token_code' => $row->token_code,
                'office' => optional($row->office)->name,
                'sub_office' => optional(optional($row->serviceType)->subOffice)->name ?? 'General Queue',
                'request_mode' => strtoupper((string) $row->request_mode),
                'serving_staff' => $row->serving_counter ?: 'Unassigned',
            ])
            ->values();

        $laneStats = ServiceRequest::query()
            ->with(['office', 'serviceType.subOffice', 'appointment'])
            ->whereIn('office_id', $officeIds)
            ->whereIn('request_mode', ['walk_in', 'appointment'])
            ->whereNull('archived_at')
            ->where('queue_stage', 'waiting')
            ->get()
            ->groupBy(function (ServiceRequest $request) {
                return implode('|', [
                    $request->office_id,
                    optional($request->serviceType)->sub_office_id ?? 'general',
                ]);
            })
            ->map(function ($requests) use ($today) {
                $sample = $requests->first();
                $appointmentWaitingToday = $requests
                    ->where('request_mode', 'appointment')
                    ->filter(fn (ServiceRequest $request) => optional($request->appointment)->appointment_date === $today)
                    ->count();
                return [
                    'office' => optional($sample->office)->name ?? 'Unknown Office',
                    'sub_office' => optional(optional($sample->serviceType)->subOffice)->name ?? 'General Queue',
                    'walk_in_waiting' => $requests->where('request_mode', 'walk_in')->count(),
                    'appointment_waiting' => $appointmentWaitingToday,
                    'tv_url' => route('queue.public.display', $sample->office_id),
                ];
            })
            ->sortBy(['office', 'sub_office'])
            ->values();

        return response()->json([
            'now_serving' => $nowServing,
            'lane_stats' => $laneStats,
            'timestamp' => QueueBusinessCalendar::now()->format('H:i:s'),
        ]);
    }
}
