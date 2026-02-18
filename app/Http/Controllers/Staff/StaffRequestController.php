<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Mail\RequestRepliedMail;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class StaffRequestController extends Controller
{
    public function index(Request $request)
    {
        $staff = auth()->user()->staff;

        $requestsQuery = ServiceRequest::with(['student.user', 'serviceType', 'office'])
            ->where('office_id', $staff->office_id)
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
        // Security check
        abort_if(
            $request->office_id !== auth()->user()->staff->office_id,
            403
        );

        $staff = auth()->user()->staff;

        if (filled($staff->campus) && optional($request->student)->campus !== $staff->campus) {
            abort(403);
        }

        if (filled($staff->faculty) && optional($request->student)->faculty !== $staff->faculty) {
            abort(403);
        }

        if (filled($staff->department)) {
            $studentDepartment = optional($request->student)->department;
            if ($studentDepartment !== $staff->department) {
                abort(403);
            }
        }

        return view('staff.requests.show', compact('request'));
    }

    // Reply to request
    public function reply(Request $r, ServiceRequest $request)
    {
        $r->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx',
            'status' => 'required|in:Submitted,In Review,Awaiting Student Response,Appointment Scheduled,Appointment Required,Resolved,Closed',
            'priority' => 'required|in:normal,urgent',
        ]);

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
}
