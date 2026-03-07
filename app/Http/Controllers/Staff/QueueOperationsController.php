<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\QueueCalendarSetting;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Staff;
use App\Models\Student;
use App\Support\QueueBusinessCalendar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class QueueOperationsController extends Controller
{
    private const ACTIVE_QUEUE_MODES = ['walk_in', 'appointment', 'online'];

    public function index()
    {
        $staff = auth()->user()->staff;
        abort_unless($staff, 403);
        $today = QueueBusinessCalendar::now()->toDateString();

        $base = $this->staffScopedRequests($staff)
            ->with(['student.user', 'serviceType', 'office'])
            ->whereNull('archived_at')
            ->whereIn('request_mode', self::ACTIVE_QUEUE_MODES);

        $nowServing = (clone $base)
            ->whereIn('queue_stage', ['serving', 'called'])
            ->orderByRaw("FIELD(queue_stage, 'serving', 'called')")
            ->orderByRaw('COALESCE(called_at, queued_at, created_at)')
            ->first();

        $nextCandidate = $this->selectNextCandidate($staff);

        $waitingWalkIns = (clone $base)
            ->where('request_mode', 'walk_in')
            ->where('queue_stage', 'waiting')
            ->whereIn('status', ['Submitted', 'Awaiting Student Response'])
            ->count();

        $waitingOnlineRequests = (clone $base)
            ->where('request_mode', 'online')
            ->where('queue_stage', 'waiting')
            ->whereIn('status', ['Submitted', 'Awaiting Student Response'])
            ->count();

        $waitingAppointments = (clone $base)
            ->where('request_mode', 'appointment')
            ->where('queue_stage', 'waiting')
            ->where('status', 'Appointment Scheduled')
            ->whereHas('appointment', fn ($appointmentQuery) => $appointmentQuery->whereDate('appointment_date', $today))
            ->count();
        $totalPendingTokens = $waitingWalkIns + $waitingOnlineRequests + $waitingAppointments + ($nowServing ? 1 : 0);

        $recentQueueEvents = (clone $base)
            ->whereIn('queue_stage', ['called', 'serving', 'completed', 'no_show'])
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $serviceTypes = ServiceType::query()
            ->where('office_id', $staff->office_id)
            ->when(
                filled($staff->sub_office_id),
                fn ($query) => $query->where('sub_office_id', $staff->sub_office_id),
                fn ($query) => $query->whereNull('sub_office_id')
            )
            ->orderBy('name')
            ->get();

        $isOfficeOpen = QueueBusinessCalendar::isOpenAt(
            QueueBusinessCalendar::now(),
            $staff->office_id,
            $staff->faculty,
            $staff->campus
        );
        $isWalkInEnabled = QueueBusinessCalendar::walkInEnabledFor(
            (int) $staff->office_id,
            filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null
        );
        $isQueueOperationsEnabled = QueueBusinessCalendar::queueOperationsEnabledFor(
            (int) $staff->office_id,
            filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null
        );

        return view('staff.queue.operations', compact(
            'nowServing',
            'nextCandidate',
            'waitingWalkIns',
            'waitingOnlineRequests',
            'waitingAppointments',
            'totalPendingTokens',
            'recentQueueEvents',
            'serviceTypes',
            'isOfficeOpen',
            'isWalkInEnabled',
            'isQueueOperationsEnabled'
        ));
    }

    public function status()
    {
        $staff = auth()->user()->staff;
        abort_unless($staff, 403);
        $today = QueueBusinessCalendar::now()->toDateString();

        $base = $this->staffScopedRequests($staff)
            ->with(['serviceType'])
            ->whereNull('archived_at')
            ->whereIn('request_mode', self::ACTIVE_QUEUE_MODES);

        $nowServing = (clone $base)
            ->whereIn('queue_stage', ['serving', 'called'])
            ->orderByRaw("FIELD(queue_stage, 'serving', 'called')")
            ->orderByRaw('COALESCE(called_at, queued_at, created_at)')
            ->first();

        $nextCandidate = $this->selectNextCandidate($staff);

        $waitingWalkIns = (clone $base)
            ->where('request_mode', 'walk_in')
            ->where('queue_stage', 'waiting')
            ->whereIn('status', ['Submitted', 'Awaiting Student Response'])
            ->count();

        $waitingOnlineRequests = (clone $base)
            ->where('request_mode', 'online')
            ->where('queue_stage', 'waiting')
            ->whereIn('status', ['Submitted', 'Awaiting Student Response'])
            ->count();

        $waitingAppointments = (clone $base)
            ->where('request_mode', 'appointment')
            ->where('queue_stage', 'waiting')
            ->where('status', 'Appointment Scheduled')
            ->whereHas('appointment', fn ($appointmentQuery) => $appointmentQuery->whereDate('appointment_date', $today))
            ->count();
        $totalPendingTokens = $waitingWalkIns + $waitingOnlineRequests + $waitingAppointments + ($nowServing ? 1 : 0);

        $recentQueueEvents = (clone $base)
            ->whereIn('queue_stage', ['called', 'serving', 'completed', 'no_show'])
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (ServiceRequest $event) => [
                'token_code' => $event->token_code,
                'request_mode' => strtoupper((string) $event->request_mode),
                'service_type' => optional($event->serviceType)->name,
                'queue_stage' => strtoupper(str_replace('_', ' ', (string) $event->queue_stage)),
            ])
            ->values();

        $isOfficeOpen = QueueBusinessCalendar::isOpenAt(
            QueueBusinessCalendar::now(),
            $staff->office_id,
            $staff->faculty,
            $staff->campus
        );
        $isWalkInEnabled = QueueBusinessCalendar::walkInEnabledFor(
            (int) $staff->office_id,
            filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null
        );
        $isQueueOperationsEnabled = QueueBusinessCalendar::queueOperationsEnabledFor(
            (int) $staff->office_id,
            filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null
        );

        return response()->json([
            'now_serving' => $nowServing?->token_code,
            'next_candidate' => $nextCandidate?->token_code,
            'waiting_walk_ins' => $waitingWalkIns,
            'waiting_online_requests' => $waitingOnlineRequests,
            'waiting_appointments' => $waitingAppointments,
            'total_pending_tokens' => $totalPendingTokens,
            'is_office_open' => $isOfficeOpen,
            'is_walk_in_enabled' => $isWalkInEnabled,
            'is_queue_operations_enabled' => $isQueueOperationsEnabled,
            'recent_queue_events' => $recentQueueEvents,
            'timestamp' => QueueBusinessCalendar::now()->format('H:i:s'),
        ]);
    }

    public function storeWalkIn(Request $request)
    {
        $staff = auth()->user()->staff;
        abort_unless($staff, 403);
        $subOfficeId = filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null;

        if (!QueueBusinessCalendar::walkInEnabledFor((int) $staff->office_id, $subOfficeId)) {
            return back()->withErrors(['queue' => 'Walk-in queue is currently inactive for this lane.'])->withInput();
        }

        $request->validate([
            'student_number' => 'required|string|exists:students,student_number',
            'description' => 'nullable|string|max:1200',
        ]);

        $student = Student::where('student_number', $request->student_number)->firstOrFail();

        if (filled($staff->campus) && $student->campus !== $staff->campus) {
            return back()->withErrors(['student_number' => 'Student campus does not match your queue lane.'])->withInput();
        }

        if (filled($staff->faculty) && $student->faculty !== $staff->faculty) {
            return back()->withErrors(['student_number' => 'Student faculty does not match your queue lane.'])->withInput();
        }

        if (filled($staff->department) && $student->department !== $staff->department) {
            return back()->withErrors(['student_number' => 'Student department does not match your queue lane.'])->withInput();
        }

        $subOfficeId = filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null;
        $resolvedServiceTypeId = (int) ServiceType::resolveOtherForLane((int) $staff->office_id, $subOfficeId)->id;

        $walkIn = ServiceRequest::create([
            'student_id' => $student->student_number,
            'office_id' => $staff->office_id,
            'service_type_id' => $resolvedServiceTypeId,
            'request_mode' => 'walk_in',
            'description' => $request->filled('description') ? $request->description : 'Walk-in queue ticket.',
            'status' => 'Submitted',
            'queue_stage' => 'waiting',
            'queued_at' => now(),
        ]);

        return back()->with('success', "Walk-in token {$walkIn->token_code} added to queue.");
    }

    public function toggleWalkIns(Request $request)
    {
        $staff = auth()->user()->staff;
        abort_unless($staff, 403);

        $enabled = $request->boolean('walk_in_enabled');
        $officeId = (int) $staff->office_id;
        $subOfficeId = filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null;
        $settings = QueueCalendarSetting::query()->firstOrNew();
        $policies = collect($settings->lane_policies ?? []);
        $matched = false;

        $updated = $policies->map(function ($policy) use ($officeId, $subOfficeId, $enabled, &$matched) {
            if (!is_array($policy)) {
                return $policy;
            }

            $isSameLane = (int) ($policy['office_id'] ?? 0) === $officeId
                && (int) ($policy['sub_office_id'] ?? 0) === (int) ($subOfficeId ?? 0);

            if (!$isSameLane) {
                return $policy;
            }

            $matched = true;
            $policy['walk_in_enabled'] = $enabled;
            return $policy;
        });

        if (!$matched) {
            $policy = QueueBusinessCalendar::lanePolicyFor($officeId, $subOfficeId);
            $updated->push([
                'office_id' => $officeId,
                'sub_office_id' => $subOfficeId,
                'appointment_quota' => (int) ($policy['appointment_quota'] ?? 1),
                'online_quota' => (int) ($policy['online_quota'] ?? 1),
                'walk_in_quota' => (int) ($policy['walk_in_quota'] ?? 2),
                'recall_timeout_seconds' => (int) ($policy['recall_timeout_seconds'] ?? 90),
                'walk_in_enabled' => $enabled,
                'queue_operations_enabled' => (bool) ($policy['queue_operations_enabled'] ?? true),
            ]);
        }

        $settings->lane_policies = $updated->values()->all();
        $settings->save();
        QueueBusinessCalendar::clearCache();

        return back()->with('success', $enabled ? 'Walk-in queue opened for this lane.' : 'Walk-in queue closed for this lane.');
    }

    public function toggleQueueOperations(Request $request)
    {
        $staff = auth()->user()->staff;
        abort_unless($staff, 403);

        $enabled = $request->boolean('queue_operations_enabled');
        $officeId = (int) $staff->office_id;
        $subOfficeId = filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null;
        $settings = QueueCalendarSetting::query()->firstOrNew();
        $policies = collect($settings->lane_policies ?? []);
        $matched = false;

        $updated = $policies->map(function ($policy) use ($officeId, $subOfficeId, $enabled, &$matched) {
            if (!is_array($policy)) {
                return $policy;
            }

            $isSameLane = (int) ($policy['office_id'] ?? 0) === $officeId
                && (int) ($policy['sub_office_id'] ?? 0) === (int) ($subOfficeId ?? 0);

            if (!$isSameLane) {
                return $policy;
            }

            $matched = true;
            $policy['queue_operations_enabled'] = $enabled;
            return $policy;
        });

        if (!$matched) {
            $policy = QueueBusinessCalendar::lanePolicyFor($officeId, $subOfficeId);
            $updated->push([
                'office_id' => $officeId,
                'sub_office_id' => $subOfficeId,
                'appointment_quota' => (int) ($policy['appointment_quota'] ?? 1),
                'online_quota' => (int) ($policy['online_quota'] ?? 1),
                'walk_in_quota' => (int) ($policy['walk_in_quota'] ?? 2),
                'recall_timeout_seconds' => (int) ($policy['recall_timeout_seconds'] ?? 90),
                'walk_in_enabled' => (bool) ($policy['walk_in_enabled'] ?? true),
                'queue_operations_enabled' => $enabled,
            ]);
        }

        $settings->lane_policies = $updated->values()->all();
        $settings->save();
        QueueBusinessCalendar::clearCache();

        if ($enabled) {
            return back()->with('success', 'Queue operations enabled for this lane.');
        }

        return back()->withErrors([
            'queue' => 'Queue operations paused for this lane.',
        ]);
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

    private function selectNextCandidate(Staff $staff): ?ServiceRequest
    {
        $today = QueueBusinessCalendar::now()->toDateString();
        $baseQuery = $this->staffScopedRequests($staff)
            ->whereNull('archived_at')
            ->whereIn('request_mode', self::ACTIVE_QUEUE_MODES)
            ->where('queue_stage', 'waiting');

        $policy = QueueBusinessCalendar::lanePolicyFor(
            (int) $staff->office_id,
            filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null
        );

        $appointments = (clone $baseQuery)
            ->where('request_mode', 'appointment')
            ->where('status', 'Appointment Scheduled')
            ->whereHas('appointment', fn ($appointmentQuery) => $appointmentQuery->whereDate('appointment_date', $today))
            ->orderByRaw('COALESCE(queued_at, created_at)')
            ->get();

        $onlineRequests = (clone $baseQuery)
            ->where('request_mode', 'online')
            ->whereIn('status', ['Submitted', 'Awaiting Student Response'])
            ->orderByRaw('COALESCE(queued_at, created_at)')
            ->get();

        $walkIns = (clone $baseQuery)
            ->where('request_mode', 'walk_in')
            ->whereIn('status', ['Submitted', 'Awaiting Student Response'])
            ->orderByRaw('COALESCE(queued_at, created_at)')
            ->get();

        $cycleLength = max(1, (int) $policy['appointment_quota'] + (int) ($policy['online_quota'] ?? 1) + (int) $policy['walk_in_quota']);
        $recent = $this->staffScopedRequests($staff)
            ->whereNull('archived_at')
            ->whereIn('request_mode', self::ACTIVE_QUEUE_MODES)
            ->whereIn('queue_stage', ['called', 'serving', 'completed'])
            ->orderByDesc('updated_at')
            ->limit($cycleLength)
            ->get();

        $appointmentServed = $recent->where('request_mode', 'appointment')->count();
        $onlineServed = $recent->where('request_mode', 'online')->count();
        $walkInServed = $recent->where('request_mode', 'walk_in')->count();

        if ($appointments->isNotEmpty() && $appointmentServed < (int) $policy['appointment_quota']) {
            return $appointments->first();
        }

        if ($onlineRequests->isNotEmpty() && $onlineServed < (int) ($policy['online_quota'] ?? 1)) {
            return $onlineRequests->first();
        }

        if ($walkIns->isNotEmpty() && $walkInServed < (int) $policy['walk_in_quota']) {
            return $walkIns->first();
        }

        return $appointments->first() ?: $onlineRequests->first() ?: $walkIns->first();
    }
}
