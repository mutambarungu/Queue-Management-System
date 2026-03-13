<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Mail\RequestRepliedMail;
use App\Models\Office;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use App\Models\ServiceType;
use App\Models\Staff;
use App\Support\QueueBusinessCalendar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class StaffRequestController extends Controller
{
    private const ACTIVE_QUEUE_MODES = ['walk_in', 'appointment', 'online'];

    public function index(Request $request)
    {
        $staff = auth()->user()->staff;

        $baseQuery = $this->staffScopedRequests($staff)
            ->whereNull('archived_at');

        $requestsQuery = (clone $baseQuery)
            ->with(['student.user', 'serviceType', 'office']);

        if ($request->filled('status')) {
            $requestsQuery->where('status', $request->status);
        }

        $requests = $requestsQuery
            ->orderBy('created_at', 'asc')
            ->get();

        $currentlyServing = (clone $baseQuery)
            ->whereIn('request_mode', self::ACTIVE_QUEUE_MODES)
            ->where('queue_stage', 'serving')
            ->orderByRaw('COALESCE(queued_at, created_at)')
            ->first();

        $nextCandidate = $this->selectNextCandidate($staff);

        return view('staff.requests.index', compact('requests', 'currentlyServing', 'nextCandidate'));
    }

    public function callNext()
    {
        $staff = auth()->user()->staff;
        if (!$staff) {
            abort(403);
        }

        if (!QueueBusinessCalendar::queueOperationsEnabledFor(
            (int) $staff->office_id,
            filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null
        )) {
            return back()->withErrors([
                'queue' => 'Queue operations are currently paused for this lane.',
            ]);
        }

        $baseQuery = $this->staffScopedRequests($staff)->whereNull('archived_at');

        $currentlyServing = (clone $baseQuery)
            ->whereIn('request_mode', self::ACTIVE_QUEUE_MODES)
            ->whereIn('queue_stage', ['called', 'serving'])
            ->exists();

        if ($currentlyServing) {
            return back()->withErrors([
                'queue' => 'A request is already in review. Resolve or update it before calling next.',
            ]);
        }

        $candidate = $this->selectNextCandidate($staff);

        if (!$candidate) {
            return back()->withErrors([
                'queue' => 'No waiting requests in your lane.',
            ]);
        }

        $candidate->queue_stage = 'called';
        $candidate->called_at = now();
        $candidate->recalled_at = null;
        $candidate->recall_count = 0;
        $candidate->no_show_at = null;
        $candidate->serving_counter = $this->servingLabel($staff);
        $candidate->save();

        return back()->with('success', "Called token {$candidate->token_code}.");
    }

    public function advanceToNext()
    {
        $staff = auth()->user()->staff;
        if (!$staff) {
            abort(403);
        }

        if (!QueueBusinessCalendar::queueOperationsEnabledFor(
            (int) $staff->office_id,
            filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null
        )) {
            return back()->withErrors([
                'queue' => 'Queue operations are currently paused for this lane.',
            ]);
        }

        $baseQuery = $this->staffScopedRequests($staff)->whereNull('archived_at');

        $active = (clone $baseQuery)
            ->whereIn('request_mode', self::ACTIVE_QUEUE_MODES)
            ->whereIn('queue_stage', ['called', 'serving'])
            ->orderByRaw("FIELD(queue_stage, 'serving', 'called')")
            ->orderByRaw('COALESCE(called_at, queued_at, created_at)')
            ->first();

        $autoOpenModes = ['online', 'appointment'];
        $autoOpenStatuses = ['Submitted', 'In Review', 'Awaiting Student Response', 'Appointment Required', 'Appointment Scheduled'];

        if (
            $active
            && in_array($active->request_mode, $autoOpenModes, true)
            && in_array($active->status, $autoOpenStatuses, true)
            && $active->queue_stage !== 'completed'
        ) {
            if ($active->status !== 'In Review' || $active->queue_stage !== 'serving') {
                $active->status = 'In Review';
                $active->queue_stage = 'serving';
                $active->called_at = $active->called_at ?: now();
                $active->no_show_at = null;
                $active->serving_counter = $this->servingLabel($staff);
                $active->save();
            }

            return redirect()
                ->route('staff.requests.show', ['request' => $active->id, 'from_queue' => 1])
                ->with('success', "Review {$active->token_code} details first. Send reply/update, then continue queue operations.");
        }

        $completedToken = null;
        if ($active) {
            $active->status = 'Resolved';
            $active->queue_stage = 'completed';
            $active->called_at = null;
            $active->recalled_at = null;
            $active->no_show_at = null;
            $active->recall_count = 0;
            $active->serving_counter = null;
            $active->save();
            $completedToken = $active->token_code;
        }

        $candidate = $this->selectNextCandidate($staff);

        if (!$candidate) {
            if ($completedToken) {
                return back()->with('success', "Completed {$completedToken}. No other waiting requests.");
            }

            return back()->withErrors([
                'queue' => 'No waiting requests in your lane.',
            ]);
        }

        $candidate->queue_stage = 'called';
        $candidate->called_at = now();
        $candidate->recalled_at = null;
        $candidate->recall_count = 0;
        $candidate->no_show_at = null;
        $candidate->serving_counter = $this->servingLabel($staff);
        $candidate->save();

        if (in_array($candidate->request_mode, $autoOpenModes, true)) {
            $message = $completedToken
                ? "Completed {$completedToken} and called {$candidate->token_code}. Review request details to continue."
                : "Called token {$candidate->token_code}. Review request details to continue.";

            return redirect()
                ->route('staff.requests.show', ['request' => $candidate->id, 'from_queue' => 1])
                ->with('success', $message);
        }

        if ($completedToken) {
            return back()->with('success', "Completed {$completedToken} and called {$candidate->token_code}.");
        }

        return back()->with('success', "Called token {$candidate->token_code}.");
    }

    public function recall(ServiceRequest $request)
    {
        $staff = auth()->user()->staff;
        $this->ensureStaffCanAccessRequest($staff, $request);

        if (!QueueBusinessCalendar::queueOperationsEnabledFor(
            (int) $staff->office_id,
            filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null
        )) {
            return back()->withErrors(['queue' => 'Queue operations are currently paused for this lane.']);
        }

        if ($request->queue_stage !== 'called') {
            return back()->withErrors(['queue' => 'Only called tokens can be recalled.']);
        }

        if ((int) $request->recall_count >= 1) {
            return back()->withErrors(['queue' => 'Recall already used for this token.']);
        }

        $request->recall_count = 1;
        $request->recalled_at = now();
        $request->called_at = now();
        $request->serving_counter = $this->servingLabel($staff);
        $request->save();

        return back()->with('success', "Token {$request->token_code} recalled.");
    }

    public function markServing(ServiceRequest $request)
    {
        $staff = auth()->user()->staff;
        $this->ensureStaffCanAccessRequest($staff, $request);

        if (!QueueBusinessCalendar::queueOperationsEnabledFor(
            (int) $staff->office_id,
            filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null
        )) {
            return back()->withErrors(['queue' => 'Queue operations are currently paused for this lane.']);
        }

        if (!in_array($request->queue_stage, ['called', 'serving'], true)) {
            return back()->withErrors(['queue' => 'Token is not currently called.']);
        }

        $request->queue_stage = 'serving';
        $request->status = 'In Review';
        $request->called_at = $request->called_at ?: now();
        $request->serving_counter = $this->servingLabel($staff);
        $request->save();

        return back()->with('success', "Now serving {$request->token_code}.");
    }

    public function show(ServiceRequest $request)
    {
        $staff = auth()->user()->staff;
        $this->ensureStaffCanAccessRequest($staff, $request);
        $request->load(['student.user', 'office', 'serviceType', 'attachments', 'replies.user']);

        if (!in_array($request->status, ['In Review', 'Resolved', 'Closed'], true)) {
            $request->status = 'In Review';
            if ($request->queue_stage === 'called') {
                $request->queue_stage = 'serving';
                $request->called_at = $request->called_at ?: now();
                $request->no_show_at = null;
                $request->serving_counter = $this->servingLabel($staff);
            }
            $request->save();
        }

        $reassignOffices = Office::with('subOffices')->orderBy('name')->get(['id', 'name']);
        $reassignSubOfficeMap = $reassignOffices->mapWithKeys(function ($office) {
            return [
                $office->id => $office->subOffices->map(fn ($subOffice) => [
                    'id' => $subOffice->id,
                    'name' => $subOffice->name,
                ])->values(),
            ];
        });

        return view('staff.requests.show', compact('request', 'reassignOffices', 'reassignSubOfficeMap'));
    }

    // Reply to request
    public function reply(Request $r, ServiceRequest $request)
    {
        $staff = auth()->user()->staff;
        $this->ensureStaffCanAccessRequest($staff, $request);

        if (
            $r->input('status') === 'In Review'
            && !QueueBusinessCalendar::queueOperationsEnabledFor(
                (int) $staff->office_id,
                filled($staff->sub_office_id) ? (int) $staff->sub_office_id : null
            )
        ) {
            return back()->withErrors([
                'status' => 'Queue operations are currently paused for this lane.',
            ])->withInput();
        }

        $r->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx',
            'status' => 'required|in:Submitted,In Review,Awaiting Student Response,Appointment Scheduled,Appointment Required,Resolved,Closed',
        ]);

        $queueStarted = $this->staffScopedRequests($staff)
            ->whereNull('archived_at')
            ->where('queue_stage', 'serving')
            ->exists();

        if (!$queueStarted && $r->status !== 'In Review') {
            return back()->withErrors([
                'status' => 'Queue not started. Set at least one request to "In Review" first.',
            ])->withInput();
        }

        $filePath = null;
        if ($r->hasFile('attachment')) {
            $filePath = $r->file('attachment')->store('request_replies', 'public');
        }

        // Save reply
        $reply = new ServiceRequestReply();
        $reply->service_request_id = $request->id;
        $reply->user_id = auth()->id();
        $reply->message = $r->message;
        $reply->attachment = $filePath;
        $reply->save();

        // Update request status
        $request->status = $r->status;
        if (in_array($r->status, ['Resolved', 'Closed'], true)) {
            $request->queue_stage = 'completed';
            $request->called_at = null;
            $request->recalled_at = null;
            $request->no_show_at = null;
            $request->recall_count = 0;
            $request->serving_counter = null;
        } elseif ($r->status === 'In Review') {
            $request->queue_stage = 'serving';
            $request->called_at = $request->called_at ?: now();
            $request->no_show_at = null;
        } else {
            $request->queue_stage = 'waiting';
            $request->called_at = null;
            $request->recalled_at = null;
            $request->no_show_at = null;
            $request->recall_count = 0;
            $request->serving_counter = null;
        }
        $request->save();

        Mail::to($request->student->user->email)
            ->send(new RequestRepliedMail($request));

        if ($r->boolean('from_queue')) {
            return redirect()
                ->route('staff.queue.operations')
                ->with('success', 'Reply sent successfully. Back to queue operations.');
        }

        return back()->with('success', 'Reply sent successfully.');
    }

    public function reassign(Request $request, ServiceRequest $serviceRequest)
    {
        $staff = auth()->user()->staff;
        $this->ensureStaffCanAccessRequest($staff, $serviceRequest);

        $request->validate([
            'new_office_id' => 'required|exists:offices,id',
            'new_sub_office_id' => 'nullable|exists:office_sub_offices,id',
        ]);

        if ((int) $request->new_office_id === (int) $serviceRequest->office_id) {
            return back()->withErrors([
                'new_office_id' => 'Please choose a different office.',
            ])->withInput();
        }

        $newOffice = Office::with('subOffices')->findOrFail($request->new_office_id);
        $newSubOfficeId = filled($request->new_sub_office_id) ? (int) $request->new_sub_office_id : null;

        if ($newOffice->subOffices->isNotEmpty() && !$newSubOfficeId) {
            return back()->withErrors([
                'new_sub_office_id' => 'Please select a sub-office for this office.',
            ])->withInput();
        }

        if ($newSubOfficeId && !$newOffice->subOffices->contains('id', $newSubOfficeId)) {
            return back()->withErrors([
                'new_sub_office_id' => 'Selected sub-office does not belong to the selected office.',
            ])->withInput();
        }

        $replacementServiceType = ServiceType::resolveOtherForLane((int) $newOffice->id, $newSubOfficeId);

        $serviceRequest->office_id = $newOffice->id;
        $serviceRequest->service_type_id = $replacementServiceType->id;
        $serviceRequest->status = 'Submitted';
        $serviceRequest->queue_stage = 'waiting';
        $serviceRequest->queued_at = now();
        $serviceRequest->next_notified_at = null;
        $serviceRequest->serving_notified_at = null;
        $serviceRequest->called_at = null;
        $serviceRequest->recalled_at = null;
        $serviceRequest->no_show_at = null;
        $serviceRequest->recall_count = 0;
        $serviceRequest->serving_counter = null;
        $serviceRequest->save();

        return redirect()
            ->route('staff.requests.index')
            ->with('success', 'Request reassigned successfully. It has been moved to the selected office queue.');
    }

    private function ensureStaffCanAccessRequest($staff, ServiceRequest $request): void
    {
        abort_if($request->office_id !== $staff->office_id, 403);

        $request->loadMissing('serviceType', 'student');
        $requestSubOfficeId = optional($request->serviceType)->sub_office_id;

        if ((int) $requestSubOfficeId !== (int) $staff->sub_office_id) {
            abort(403);
        }

        if (filled($staff->campus) && optional($request->student)->campus !== $staff->campus) {
            abort(403);
        }

        if (filled($staff->faculty) && optional($request->student)->faculty !== $staff->faculty) {
            abort(403);
        }

        if (filled($staff->department) && optional($request->student)->department !== $staff->department) {
            abort(403);
        }
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

    private function servingLabel(Staff $staff): string
    {
        return trim((string) ($staff->counter_number ?: $staff->name ?: optional($staff->user)->name ?: 'Staff'));
    }
}
