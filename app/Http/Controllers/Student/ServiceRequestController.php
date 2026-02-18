<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Mail\OfficeNewRequestMail;
use App\Mail\RequestRepliedMail;
use App\Mail\RequestSubmittedMail;
use App\Models\Office;
use App\Models\RequestAttachment;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ServiceRequestController extends Controller
{
    public function index()
    {
        $student = Auth::user()->student;

        if (!$student) {
            return back()->with('error', 'Student profile not found.');
        }

        $requests = ServiceRequest::with(['office', 'serviceType'])
            ->where('student_id', $student->student_number)
            ->latest()
            ->paginate(10);

        return view('student.requests.index', compact('requests'));
    }

    public function create()
    {
        $offices = Office::with('serviceTypes')->get();
        return view('student.requests.create', compact('offices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'office_id' => 'required|exists:offices,id',
            'service_type_id' => 'required|exists:service_types,id',
            'description' => 'nullable|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120'
        ]);

        $studentId = Auth::user()->student->student_number;

        $serviceRequest = ServiceRequest::create([
            'student_id' => $studentId,
            'office_id' => $request->office_id,
            'service_type_id' => $request->service_type_id,
            'description' => $request->description,
            'status' => 'Submitted',
            'priority' => 'normal',
            'queued_at' => now(),
        ]);

        // Upload files
        if ($request->hasFile('attachments')) {
            foreach ($request->attachments as $file) {
                $path = $file->store('requests', 'public');

                RequestAttachment::create([
                    'service_request_id' => $serviceRequest->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName()
                ]);
            }
        }

        // 1️⃣ Notify student
        Mail::to(Auth::user()->email)
            ->send(new RequestSubmittedMail($serviceRequest));

        /** -------------------------
         * 2️⃣ Notify Staff in Office
         * ------------------------- */
        $staffMembers = Staff::with('user')
            ->where('office_id', $request->office_id)
            ->get();

        foreach ($staffMembers as $staff) {
            if ($staff->user && $staff->user->email) {
                Mail::to($staff->user->email)
                    ->send(new OfficeNewRequestMail($serviceRequest));
            }
        }

        return back()->with('success', 'Request submitted successfully!');
    }
    public function show(ServiceRequest $request)
    {

        $request->load(['office', 'serviceType', 'attachments', 'replies']);

        $requests = ServiceRequest::where('office_id', $request->office_id)
            ->whereNull('archived_at')
            ->whereIn('status', ['Submitted', 'In Review', 'Awaiting Student Response'])
            ->orderByRaw("FIELD(priority, 'urgent', 'normal')")
            ->orderBy('queued_at')
            ->paginate(15);

        return view('student.requests.show', compact('request', 'requests'));
    }

    // Reply to request
    public function reply(Request $r, ServiceRequest $request)
    {
        $r->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx',
            'status' => 'required|in:Submitted,In Review,Resolved,Closed'
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
        $request->save();

        Mail::to($request->student->user->email)
            ->send(new RequestRepliedMail($request));

        return back()->with('success', 'Reply sent successfully.');
    }

    public function status(ServiceRequest $request)
    {
        $request->load('office');

        return response()->json([
            'queue_position' => $request->queue_position,
            'people_ahead' => $request->people_ahead,
            'estimated_wait' => $request->estimated_wait_time,
            'currently_serving' => optional($request->currently_serving)->request_number,
            'status' => $request->status,
        ]);
    }
}
