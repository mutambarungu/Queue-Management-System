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
use App\Models\ServiceType;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class ServiceRequestController extends Controller
{
    private const OTHER_SERVICE_TYPE_VALUE = '__other__';
    private const OTHER_SERVICE_TYPE_NAME = 'Other (Not specified)';

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
        $offices = Office::with(['serviceTypes', 'subOffices'])->get();
        $officeSubOfficeMap = $offices->mapWithKeys(function ($office) {
            return [
                $office->id => $office->subOffices->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'name' => $sub->name,
                    ];
                })->values(),
            ];
        });

        $officeServiceTypeMap = $offices->mapWithKeys(function ($office) {
            return [
                $office->id => $office->serviceTypes->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'sub_office_id' => $service->sub_office_id,
                    ];
                })->values(),
            ];
        });

        return view('student.requests.create', compact('offices', 'officeSubOfficeMap', 'officeServiceTypeMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'office_id' => 'required|exists:offices,id',
            'sub_office_id' => 'nullable|integer',
            'service_type_id' => 'required',
            'description' => 'nullable|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120'
        ]);

        $office = Office::with('subOffices')->findOrFail($request->office_id);
        $officeHasSubOffices = $office->subOffices->isNotEmpty();
        $subOfficeId = null;

        if ($officeHasSubOffices) {
            $request->validate([
                'sub_office_id' => [
                    'required',
                    Rule::exists('office_sub_offices', 'id')->where(
                        fn ($query) => $query->where('office_id', $request->office_id)
                    ),
                ],
            ]);

            $subOfficeId = (int) $request->sub_office_id;
        }

        $selectedServiceType = (string) $request->service_type_id;
        $isOtherSelected = $selectedServiceType === self::OTHER_SERVICE_TYPE_VALUE;

        if ($isOtherSelected) {
            $request->validate([
                'description' => 'required|string|min:5',
            ]);
        } else {
            $request->validate([
                'service_type_id' => [
                    'required',
                    Rule::exists('service_types', 'id')->where(function ($query) use ($request, $officeHasSubOffices, $subOfficeId) {
                        $query->where('office_id', $request->office_id);

                        if ($officeHasSubOffices) {
                            $query->where('sub_office_id', $subOfficeId);
                        } else {
                            $query->whereNull('sub_office_id');
                        }
                    }),
                ],
            ]);
        }

        $resolvedServiceTypeId = $isOtherSelected
            ? ServiceType::firstOrCreate(
                [
                    'office_id' => $request->office_id,
                    'sub_office_id' => $officeHasSubOffices ? $subOfficeId : null,
                    'name' => self::OTHER_SERVICE_TYPE_NAME,
                ]
            )->id
            : (int) $request->service_type_id;

        $studentId = Auth::user()->student->student_number;

        $serviceRequest = ServiceRequest::create([
            'student_id' => $studentId,
            'office_id' => $request->office_id,
            'service_type_id' => $resolvedServiceTypeId,
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

        $response = back()->with('success', 'Request submitted successfully!');
        $studentEmail = Auth::user()->email;
        $requestOfficeId = (int) $request->office_id;
        $serviceRequestId = (int) $serviceRequest->id;

        // Send notifications after response so UI is not blocked by SMTP latency/failures.
        app()->terminating(function () use ($studentEmail, $requestOfficeId, $serviceRequestId) {
            $storedRequest = ServiceRequest::with('serviceType')->find($serviceRequestId);
            if (!$storedRequest) {
                return;
            }

            $this->sendMailSafely($studentEmail, new RequestSubmittedMail($storedRequest));

            $serviceSubOfficeId = optional($storedRequest->serviceType)->sub_office_id;
            $staffMembers = Staff::with('user')
                ->where('office_id', $requestOfficeId)
                ->when(
                    filled($serviceSubOfficeId),
                    fn ($query) => $query->where('sub_office_id', $serviceSubOfficeId),
                    fn ($query) => $query->whereNull('sub_office_id')
                )
                ->get();

            foreach ($staffMembers as $staff) {
                if ($staff->user && $staff->user->email) {
                    $this->sendMailSafely($staff->user->email, new OfficeNewRequestMail($storedRequest));
                }
            }
        });

        return $response;
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

        $studentEmail = optional(optional($request->student)->user)->email;
        $serviceRequestId = (int) $request->id;

        if (filled($studentEmail)) {
            app()->terminating(function () use ($studentEmail, $serviceRequestId) {
                $storedRequest = ServiceRequest::find($serviceRequestId);
                if (!$storedRequest) {
                    return;
                }

                $this->sendMailSafely($studentEmail, new RequestRepliedMail($storedRequest));
            });
        }

        return back()->with('success', 'Reply sent successfully.');
    }

    public function status(ServiceRequest $request)
    {
        $request->load(['office', 'serviceType.subOffice']);

        return response()->json([
            'queue_position' => $request->queue_position,
            'people_ahead' => $request->people_ahead,
            'currently_serving' => optional($request->currently_serving)->queue_position,
            'next_in_line' => optional($request->next_in_line)->queue_position,
            'queue_state' => $request->queue_state,
            'lane_label' => optional(optional($request->serviceType)->subOffice)->name ?: 'General Queue',
            'status' => $request->status,
            'last_updated_at' => now()->format('H:i:s'),
        ]);
    }

    private function sendMailSafely(string $to, $mailable): bool
    {
        try {
            Mail::to($to)->send($mailable);
            return true;
        } catch (Throwable $e) {
            Log::warning('Mail send failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
