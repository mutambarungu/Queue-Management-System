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
use App\Models\Student;
use App\Models\User;
use App\Support\QueueBusinessCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class ServiceRequestController extends Controller
{
    private const OTHER_SERVICE_TYPE_VALUE = '__other__';
    private const QR_JOIN_SESSION_KEY = 'student_queue_join_lane';
    private const LIVE_QUEUE_SESSION_KEY = 'student_live_queue_lane';
    private const GUEST_STUDENT_SESSION_KEY = 'guest_queue_student_number';

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

    public function trackQueue(ServiceRequest $request, Request $httpRequest)
    {
        $student = Auth::user()->student;
        abort_unless($student, 403);
        abort_unless($request->student_id === $student->student_number, 403);

        $request->loadMissing('serviceType');
        $subOfficeId = optional($request->serviceType)->sub_office_id;

        $httpRequest->session()->put(self::LIVE_QUEUE_SESSION_KEY, [
            'office_id' => (int) $request->office_id,
            'sub_office_id' => filled($subOfficeId) ? (int) $subOfficeId : null,
            'service_request_id' => (int) $request->id,
            'token_code' => (string) $request->token_code,
            'scanned_at' => now()->timestamp,
        ]);

        return redirect()->route('student.queue.live');
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
            $deduped = $office->serviceTypes
                ->sortBy('name')
                ->unique(function ($service) {
                    $normalizedName = ServiceType::normalizeName($service->name);
                    $laneKey = filled($service->sub_office_id) ? (string) $service->sub_office_id : 'general';
                    return $laneKey . '|' . $normalizedName;
                })
                ->values();

            return [
                $office->id => $deduped->map(function ($service) {
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
            ? ServiceType::resolveOtherForLane(
                (int) $request->office_id,
                $officeHasSubOffices ? $subOfficeId : null
            )->id
            : (int) $request->service_type_id;

        $studentId = Auth::user()->student->student_number;

        $serviceRequest = ServiceRequest::create([
            'student_id' => $studentId,
            'office_id' => $request->office_id,
            'service_type_id' => $resolvedServiceTypeId,
            'request_mode' => 'online',
            'description' => $request->description,
            'status' => 'Submitted',
            'queue_stage' => 'waiting',
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
        $request->queue_stage = in_array($r->status, ['Resolved', 'Closed'], true)
            ? 'completed'
            : ($r->status === 'In Review' ? 'serving' : 'waiting');
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

    public function liveQueue(Request $request)
    {
        $liveLane = $request->session()->get(self::LIVE_QUEUE_SESSION_KEY);
        $scannedOfficeId = is_array($liveLane) ? (int) ($liveLane['office_id'] ?? 0) : 0;
        $liveRequestId = is_array($liveLane) ? (int) ($liveLane['service_request_id'] ?? 0) : 0;

        if ($scannedOfficeId <= 0) {
            return view('student.queue.live', [
                'office' => null,
                'watchOffice' => null,
                'myToken' => null,
                'myLane' => null,
            ]);
        }

        $watchOffice = Office::query()->find($scannedOfficeId);
        $activeRequest = null;

        if ($liveRequestId > 0) {
            $activeRequest = ServiceRequest::query()
                ->with(['office', 'serviceType.subOffice'])
                ->where('id', $liveRequestId)
                ->where('office_id', $scannedOfficeId)
                ->whereNull('archived_at')
                ->whereIn('status', ['Submitted', 'In Review', 'Awaiting Student Response', 'Appointment Scheduled'])
                ->whereNotIn('queue_stage', ['completed', 'no_show'])
                ->whereIn('request_mode', ['walk_in', 'appointment', 'online'])
                ->first();
        }

        if (!$activeRequest) {
            $student = optional(Auth::user())->student;
            if ($student) {
                $activeRequest = ServiceRequest::query()
                    ->with(['office', 'serviceType.subOffice'])
                    ->where('student_id', $student->student_number)
                    ->where('office_id', $scannedOfficeId)
                    ->whereNull('archived_at')
                    ->whereIn('status', ['Submitted', 'In Review', 'Awaiting Student Response', 'Appointment Scheduled'])
                    ->whereNotIn('queue_stage', ['completed', 'no_show'])
                    ->whereIn('request_mode', ['walk_in', 'appointment', 'online'])
                    ->orderByRaw("FIELD(queue_stage, 'serving', 'called', 'waiting')")
                    ->orderByRaw('COALESCE(called_at, queued_at, created_at)')
                    ->first();
            }
        }

        return view('student.queue.live', [
            'office' => optional($activeRequest)->office ?? $watchOffice,
            'watchOffice' => $watchOffice,
            'myToken' => optional($activeRequest)->token_code,
            'myLane' => optional(optional(optional($activeRequest)->serviceType)->subOffice)->name ?? 'General Queue',
        ]);
    }

    public function showJoinQueueFromQr(Request $request)
    {
        $officeId = (int) $request->integer('office_id');
        $subOfficeId = $request->filled('sub_office_id') ? (int) $request->integer('sub_office_id') : null;

        $office = Office::with('subOffices')->findOrFail($officeId);

        if ($subOfficeId) {
            $subOffice = $office->subOffices->firstWhere('id', $subOfficeId);
            abort_unless($subOffice, 404);
        } else {
            $subOffice = null;
        }

        $student = optional(Auth::user())->student;
        $faculty = optional($student)->faculty;
        $campus = optional($student)->campus;

        $now = QueueBusinessCalendar::now();
        $isOfficeOpen = QueueBusinessCalendar::isOpenAt(
            $now,
            $office->id,
            $faculty,
            $campus
        );
        $isWalkInEnabled = QueueBusinessCalendar::walkInEnabledFor($office->id, $subOfficeId);
        $canJoin = $isOfficeOpen && $isWalkInEnabled;

        $request->session()->put(self::QR_JOIN_SESSION_KEY, [
            'office_id' => $office->id,
            'sub_office_id' => $subOfficeId,
            'granted_at' => now()->timestamp,
        ]);
        $request->session()->put(self::LIVE_QUEUE_SESSION_KEY, [
            'office_id' => $office->id,
            'sub_office_id' => $subOfficeId,
            'scanned_at' => now()->timestamp,
        ]);

        $closureMessage = !$isOfficeOpen
            ? (QueueBusinessCalendar::closureMessage($now, $office->id, $faculty, $campus) ?? 'Queue is inactive right now.')
            : null;
        $queueHours = QueueBusinessCalendar::hoursDescription($office->id, $faculty, $campus);

        return view('student.queue.join', [
            'office' => $office,
            'subOffice' => $subOffice,
            'canJoin' => $canJoin,
            'isOfficeOpen' => $isOfficeOpen,
            'isWalkInEnabled' => $isWalkInEnabled,
            'closureMessage' => $closureMessage,
            'queueHours' => $queueHours,
        ]);
    }

    public function storeJoinQueueFromQr(Request $request)
    {
        $validated = $request->validate([
            'office_id' => 'required|integer|exists:offices,id',
            'sub_office_id' => 'nullable|integer',
        ]);

        $sessionGrant = $request->session()->get(self::QR_JOIN_SESSION_KEY);
        $officeId = (int) $validated['office_id'];
        $subOfficeId = filled($validated['sub_office_id'] ?? null) ? (int) $validated['sub_office_id'] : null;

        if (
            !is_array($sessionGrant)
            || (int) ($sessionGrant['office_id'] ?? 0) !== $officeId
            || (int) ($sessionGrant['sub_office_id'] ?? 0) !== (int) ($subOfficeId ?? 0)
        ) {
            return back()->withErrors([
                'queue' => 'Please scan a valid office QR code before joining the queue.',
            ]);
        }

        $office = Office::with('subOffices')->findOrFail($officeId);
        if ($subOfficeId) {
            abort_unless($office->subOffices->contains('id', $subOfficeId), 404);
        }

        $student = optional(Auth::user())->student;
        if (!$student) {
            $student = $this->resolveGuestStudent($request);
        }

        $now = QueueBusinessCalendar::now();
        $isOfficeOpen = QueueBusinessCalendar::isOpenAt($now, $officeId, optional($student)->faculty, optional($student)->campus);
        if (!$isOfficeOpen) {
            $message = QueueBusinessCalendar::closureMessage($now, $officeId, optional($student)->faculty, optional($student)->campus)
                ?? 'Queue is inactive right now.';
            return back()->withErrors(['queue' => $message]);
        }

        if (!QueueBusinessCalendar::walkInEnabledFor($officeId, $subOfficeId)) {
            return back()->withErrors([
                'queue' => 'Queue is inactive for walk-ins at the moment. Please try again later.',
            ]);
        }

        $existingActive = ServiceRequest::query()
            ->where('student_id', $student->student_number)
            ->where('office_id', $officeId)
            ->whereNull('archived_at')
            ->whereIn('status', ['Submitted', 'In Review', 'Awaiting Student Response', 'Appointment Scheduled'])
            ->whereNotIn('queue_stage', ['completed', 'no_show'])
            ->whereHas('serviceType', function ($serviceTypeQuery) use ($subOfficeId) {
                if (filled($subOfficeId)) {
                    $serviceTypeQuery->where('sub_office_id', $subOfficeId);
                } else {
                    $serviceTypeQuery->whereNull('sub_office_id');
                }
            })
            ->orderByRaw("FIELD(queue_stage, 'called', 'serving', 'waiting')")
            ->orderByRaw('COALESCE(queued_at, created_at)')
            ->first();

        if ($existingActive) {
            $request->session()->put(self::LIVE_QUEUE_SESSION_KEY, [
                'office_id' => $officeId,
                'sub_office_id' => $subOfficeId,
                'service_request_id' => (int) $existingActive->id,
                'token_code' => $existingActive->token_code,
                'scanned_at' => now()->timestamp,
            ]);

            return back()
                ->with('success', "You already have an active token ({$existingActive->token_code}) in this lane.")
                ->with('joined_token', $existingActive->token_code);
        }

        $serviceTypeId = $this->resolveServiceTypeForLane($officeId, $subOfficeId);
        $queueRequest = ServiceRequest::create([
            'student_id' => $student->student_number,
            'office_id' => $officeId,
            'service_type_id' => $serviceTypeId,
            'request_mode' => 'walk_in',
            'description' => 'Joined via office QR code.',
            'status' => 'Submitted',
            'queue_stage' => 'waiting',
            'queued_at' => now(),
        ]);

        $request->session()->forget(self::QR_JOIN_SESSION_KEY);
        $request->session()->put(self::LIVE_QUEUE_SESSION_KEY, [
            'office_id' => $officeId,
            'sub_office_id' => $subOfficeId,
            'service_request_id' => (int) $queueRequest->id,
            'token_code' => $queueRequest->token_code,
            'scanned_at' => now()->timestamp,
        ]);

        return back()
            ->with('success', "You joined the queue. Your token is {$queueRequest->token_code}.")
            ->with('joined_token', $queueRequest->token_code);
    }

    private function resolveServiceTypeForLane(int $officeId, ?int $subOfficeId = null): int
    {
        $serviceType = ServiceType::query()
            ->where('office_id', $officeId)
            ->when(
                filled($subOfficeId),
                fn ($query) => $query->where('sub_office_id', $subOfficeId),
                fn ($query) => $query->whereNull('sub_office_id')
            )
            ->orderBy('id')
            ->first();

        if ($serviceType) {
            return (int) $serviceType->id;
        }

        return (int) ServiceType::resolveOtherForLane($officeId, $subOfficeId)->id;
    }

    private function resolveGuestStudent(Request $request): Student
    {
        $storedStudentNumber = (string) $request->session()->get(self::GUEST_STUDENT_SESSION_KEY, '');
        if ($storedStudentNumber !== '') {
            $existing = Student::query()->where('student_number', $storedStudentNumber)->first();
            if ($existing) {
                return $existing;
            }
        }

        do {
            $suffix = strtoupper(Str::random(8));
            $guestStudentNumber = 'GUEST-' . now()->format('Ymd') . '-' . $suffix;
            $guestEmail = strtolower($guestStudentNumber) . '@guest.queue.local';
        } while (
            Student::query()->where('student_number', $guestStudentNumber)->exists()
            || User::query()->where('email', $guestEmail)->exists()
        );

        $guestUser = User::query()->create([
            'email' => $guestEmail,
            'password' => Hash::make(Str::random(48)),
            'role' => 'student',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $guestStudent = Student::query()->create([
            'student_number' => $guestStudentNumber,
            'name' => 'Guest Visitor',
            'user_id' => $guestUser->id,
            'campus' => null,
            'faculty' => null,
            'department' => null,
            'phone' => null,
        ]);

        $guestUser->student_number = $guestStudent->student_number;
        $guestUser->save();

        $request->session()->put(self::GUEST_STUDENT_SESSION_KEY, $guestStudent->student_number);

        return $guestStudent;
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
