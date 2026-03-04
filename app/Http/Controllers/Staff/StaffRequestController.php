<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Mail\RequestRepliedMail;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use App\Models\Staff;
use App\Support\QueueBusinessCalendar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class StaffRequestController extends Controller
{
    public function index(Request $request)
    {
        $staff = auth()->user()->staff;

        $requestsQuery = $this->staffScopedRequests($staff)
            ->with(['student.user', 'serviceType', 'office']);

        if ($request->filled('status')) {
            $requestsQuery->where('status', $request->status);
        }

        $requests = $requestsQuery
            ->orderByRaw("FIELD(priority, 'urgent', 'normal')") // Urgent first
            ->orderBy('created_at', 'asc')
            ->get();

        return view('staff.requests.index', compact('requests'));
    }

    public function show(ServiceRequest $request)
    {
        $staff = auth()->user()->staff;
        $this->ensureStaffCanAccessRequest($staff, $request);

        return view('staff.requests.show', compact('request'));
    }

    // Reply to request
    public function reply(Request $r, ServiceRequest $request)
    {
        $staff = auth()->user()->staff;
        $this->ensureStaffCanAccessRequest($staff, $request);

        $r->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx',
            'status' => 'required|in:Submitted,In Review,Awaiting Student Response,Appointment Scheduled,Appointment Required,Resolved,Closed',
            'priority' => 'required|in:normal,urgent',
        ]);

        $queueStarted = $this->staffScopedRequests($staff)
            ->whereNull('archived_at')
            ->where('status', 'In Review')
            ->exists();

        if (!$queueStarted && $r->status !== 'In Review') {
            return back()->withErrors([
                'status' => 'Queue not started. Set at least one request to "In Review" first.',
            ])->withInput();
        }

        if ($r->status === 'In Review') {
            $isOpenNow = QueueBusinessCalendar::isOpenAt(
                QueueBusinessCalendar::now(),
                $request->office_id,
                optional($request->student)->faculty,
                optional($request->student)->campus
            );

            if (!$isOpenNow) {
                return back()->withErrors([
                    'status' => QueueBusinessCalendar::closureMessage(
                        QueueBusinessCalendar::now(),
                        $request->office_id,
                        optional($request->student)->faculty,
                        optional($request->student)->campus
                    ) ?? 'Office currently closed. Queue progress is paused.',
                ])->withInput();
            }

            if ($request->priority === 'urgent' && $this->shouldPreferNormal($staff)) {
                return back()->withErrors([
                    'status' => 'Fairness rule: serve one normal-priority request after three urgent requests.',
                ])->withInput();
            }
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
        $request->priority = $r->priority;
        $request->save();

        Mail::to($request->student->user->email)
            ->send(new RequestRepliedMail($request));

        return back()->with('success', 'Reply sent successfully.');
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
            ->whereHas('student', function ($studentQuery) use ($staff) {
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

    private function shouldPreferNormal(Staff $staff): bool
    {
        $recentPriorities = $this->staffScopedRequests($staff)
            ->whereNull('archived_at')
            ->whereIn('status', ['In Review', 'Resolved', 'Closed'])
            ->orderByDesc('updated_at')
            ->limit(3)
            ->pluck('priority')
            ->values();

        if ($recentPriorities->count() < 3 || $recentPriorities->contains(fn ($priority) => $priority !== 'urgent')) {
            return false;
        }

        return $this->staffScopedRequests($staff)
            ->whereNull('archived_at')
            ->whereIn('status', ['Submitted', 'Awaiting Student Response'])
            ->where('priority', 'normal')
            ->exists();
    }
}
