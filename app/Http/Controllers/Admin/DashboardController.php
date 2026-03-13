<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\ServiceRequest;
use App\Models\Office;
use App\Support\QueueBusinessCalendar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | 1. Enforce Student Profile Completion (students only)
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'student') {
            if (! $user->student || ! $user->student->isProfileComplete()) {
                return redirect()->route('student.profile.complete');
            }
        }

        $dashboardData = $this->buildDashboardData($user);

        return view('dashboard', compact(
            'dashboardData'
        ));
    }

    public function liveStats(Request $request): JsonResponse
    {
        $dashboardData = $this->buildDashboardData($request->user());

        return response()->json($dashboardData);
    }

    private function applyStaffScope(Builder $query, Staff $staff): void
    {
        if (filled($staff->office_id)) {
            $query->where('office_id', $staff->office_id);
        }

        $query->whereHas('serviceType', function ($serviceTypeQuery) use ($staff) {
            if (filled($staff->sub_office_id)) {
                $serviceTypeQuery->where('sub_office_id', $staff->sub_office_id);
            } else {
                $serviceTypeQuery->whereNull('sub_office_id');
            }
        });

        $query->whereHas('student', function ($studentQuery) use ($staff) {
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
    }

    private function buildDashboardData($user): array
    {
        $requestsQuery = ServiceRequest::query();

        switch ($user->role) {
            case 'admin':
                break;
            case 'staff':
                $this->applyStaffScope($requestsQuery, $user->staff);
                break;
            case 'student':
                $requestsQuery->where('student_id', optional($user->student)->student_number);
                break;
        }

        $requestsPerOfficeQuery = Office::query();
        if ($user->role === 'staff' && filled(optional($user->staff)->office_id)) {
            $requestsPerOfficeQuery->whereKey($user->staff->office_id);
        }

        $requestsPerOffice = $requestsPerOfficeQuery->withCount([
            'requests' => function ($query) use ($user) {
                if ($user->role === 'student') {
                    $query->where('student_id', optional($user->student)->student_number);
                }

                if ($user->role === 'staff') {
                    $this->applyStaffScope($query, $user->staff);
                }
            }
        ])->get();

        $requestsPerStatus = (clone $requestsQuery)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalRequests = (clone $requestsQuery)->count();
        $pendingRequests = (clone $requestsQuery)
            ->whereIn('status', ['Submitted', 'In Review', 'Awaiting Student Response'])
            ->count();
        $resolvedRequests = (clone $requestsQuery)->where('status', 'Resolved')->count();
        $appointmentRequired = (clone $requestsQuery)->where('status', 'Appointment Scheduled')->count();

        $officeIdsForRealtime = $user->role === 'admin'
            ? Office::query()->pluck('id')->values()
            : collect([optional($user->staff)->office_id])->filter()->values();

        $studentQueueTracker = null;
        if ($user->role === 'student' && filled(optional($user->student)->student_number)) {
            $activeQueueRequest = ServiceRequest::query()
                ->with(['office', 'serviceType.subOffice'])
                ->where('student_id', $user->student->student_number)
                ->whereNull('archived_at')
                ->whereIn('status', ['Submitted', 'In Review', 'Awaiting Student Response', 'Appointment Scheduled'])
                ->whereNotIn('queue_stage', ['completed', 'no_show'])
                ->orderByRaw("FIELD(queue_stage, 'called', 'serving', 'waiting')")
                ->orderByRaw('COALESCE(called_at, queued_at, created_at)')
                ->first();

            if ($activeQueueRequest) {
                $studentQueueTracker = [
                    'request_id' => (int) $activeQueueRequest->id,
                    'token_code' => $activeQueueRequest->token_code,
                    'office_name' => optional($activeQueueRequest->office)->name ?? 'Office',
                    'lane_label' => optional(optional($activeQueueRequest->serviceType)->subOffice)->name ?? 'General Queue',
                    'request_mode' => strtoupper((string) $activeQueueRequest->request_mode),
                    'queue_position' => (int) $activeQueueRequest->queue_position,
                    'people_ahead' => (int) $activeQueueRequest->people_ahead,
                    'queue_state' => (string) $activeQueueRequest->queue_state,
                    'status' => (string) $activeQueueRequest->status,
                    'show_url' => route('student.requests.show', $activeQueueRequest),
                    'updated_at' => now()->format('H:i:s'),
                ];
            }
        }

        $nextAppointment = null;
        if ($user->role === 'student' && filled(optional($user->student)->student_number)) {
            $studentNumber = $user->student->student_number;
            $nextAppointmentModel = Appointment::query()
                ->whereHas('serviceRequest', function ($query) use ($studentNumber) {
                    $query->where('student_id', $studentNumber)
                        ->whereNull('archived_at')
                        ->where('status', 'Appointment Scheduled');
                })
                ->whereDate('appointment_date', '>=', now()->toDateString())
                ->with(['serviceRequest.office', 'serviceRequest.serviceType'])
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->first();

            if ($nextAppointmentModel) {
                $appointmentDateTime = Carbon::parse(
                    $nextAppointmentModel->appointment_date . ' ' . $nextAppointmentModel->appointment_time,
                    QueueBusinessCalendar::settings()['timezone']
                );
                $serviceRequest = $nextAppointmentModel->serviceRequest;
                $officeName = optional(optional($serviceRequest)->office)->name ?? 'Office';
                $serviceName = optional(optional($serviceRequest)->serviceType)->name ?? 'Service Request';
                $location = $nextAppointmentModel->location ?: $officeName;

                $nextAppointment = [
                    'id' => (int) $nextAppointmentModel->id,
                    'iso' => $appointmentDateTime->toIso8601String(),
                    'display' => $appointmentDateTime->format('D, M j, Y g:i A'),
                    'title' => $serviceName . ' Appointment',
                    'location' => $location,
                    'office_name' => $officeName,
                    'service_name' => $serviceName,
                    'show_url' => route('student.appointments.show', $nextAppointmentModel),
                ];
            }
        }

        return [
            'totalRequests' => $totalRequests,
            'pendingRequests' => $pendingRequests,
            'resolvedRequests' => $resolvedRequests,
            'appointmentRequired' => $appointmentRequired,
            'requestsPerOffice' => [
                'labels' => $requestsPerOffice->pluck('name')->values(),
                'counts' => $requestsPerOffice->pluck('requests_count')->values(),
            ],
            'requestsPerStatus' => [
                'labels' => $requestsPerStatus->keys()->values(),
                'counts' => $requestsPerStatus->values()->values(),
            ],
            'realtimeOfficeIds' => $officeIdsForRealtime,
            'studentQueueTracker' => $studentQueueTracker,
            'nextAppointment' => $nextAppointment,
        ];
    }
}
