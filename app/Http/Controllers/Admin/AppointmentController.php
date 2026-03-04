<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AppointmentScheduledMail;
use App\Models\Appointment;
use App\Models\ServiceRequest;
use App\Support\QueueBusinessCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with([
            'serviceRequest.student.user',
            'staff.user',
            'staff.office'
        ])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(10);

        return view('admin.appointments.index', compact('appointments'));
    }

    public function show(Appointment $appointment)
    {
        $appointment->load([
            'serviceRequest.student.user',
            'staff.user',
            'staff.office'
        ]);

        return view('admin.appointments.show', compact('appointment'));
    }

    public function showStudent(Appointment $appointment)
    {
        $student = auth()->user()->student;

        if (!$student || $appointment->serviceRequest->student_id !== $student->student_number) {
            abort(403, 'Unauthorized access.');
        }

        $appointment->load([
            'serviceRequest.student.user',
            'staff.user',
            'staff.office'
        ]);

        return view('student.appointments.show', compact('appointment'));
    }

    public function showStaff(Appointment $appointment)
    {
        $appointment->load([
            'serviceRequest.student.user',
            'staff.user',
            'staff.office'
        ]);

        return view('staff.appointments.show', compact('appointment'));
    }

    public function cancel(Appointment $appointment)
    {
        $appointment->delete();

        $appointment->serviceRequest->update([
            'status' => 'In Review'
        ]);

        return back()->with('success', 'Appointment cancelled successfully.');
    }

    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $staff = auth()->user()->staff;

        if (!$staff) {
            abort(403, 'Staff profile not found.');
        }

        $validated = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'location'         => 'nullable|string|max:255',
        ]);

        $this->assertAppointmentSlotAllowed(
            $serviceRequest,
            $validated['appointment_date'],
            $validated['appointment_time']
        );

        DB::transaction(function () use ($validated, $staff, $serviceRequest) {

            // 🔒 Prevent double booking (same staff + date + time)
            $exists = Appointment::where('staff_number', $staff->staff_number)
                ->where('appointment_date', $validated['appointment_date'])
                ->where('appointment_time', $validated['appointment_time'])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new \Exception('This time slot is already booked.');
            }

            // Create appointment
            Appointment::create([
                'service_request_id' => $serviceRequest->id,
                'staff_number'       => $staff->staff_number,
                'appointment_date'   => $validated['appointment_date'],
                'appointment_time'   => $validated['appointment_time'],
                'location'           => $validated['location'],
            ]);

            // Update request status
            $serviceRequest->update([
                'status' => 'Appointment Scheduled',
            ]);
        });

        Mail::to($serviceRequest->student->user->email)
            ->send(new AppointmentScheduledMail($serviceRequest));

        return back()->with('success', 'Appointment scheduled successfully.');
    }

    // staff views appointment list
    public function staffIndex(Request $request)
    {
        $staff = auth()->user()->staff;
        $filter = $request->query('filter', 'all');

        $appointmentsQuery = Appointment::with(['serviceRequest.student.user', 'staff.user', 'staff.office'])
            ->where('staff_number', $staff->staff_number);

        if ($filter === 'today') {
            $appointmentsQuery->whereDate('appointment_date', now()->toDateString());
        } elseif ($filter === 'tomorrow') {
            $appointmentsQuery->whereDate('appointment_date', now()->addDay()->toDateString());
        }

        $appointments = $appointmentsQuery
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(10)
            ->withQueryString();

        return view('staff.appointments.index', compact('appointments', 'filter'));
    }

    // student views appointment list
    public function studentIndex()
    {
        $student = auth()->user()->student;
        $appointments = Appointment::with(['serviceRequest.serviceType', 'staff.user'])
            ->whereHas('serviceRequest', function ($query) use ($student) {
                $query->where('student_id', $student->student_number);
            })
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(10);
        return view('student.appointments.index', compact('appointments'));
    }

    public function reschedule(Request $request, Appointment $appointment)
    {
        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required'
        ]);

        $this->assertAppointmentSlotAllowed(
            $appointment->serviceRequest,
            $request->appointment_date,
            $request->appointment_time
        );

        $appointment->update([
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Appointment rescheduled successfully.');
    }

    public function rescheduleStaff(Request $request, Appointment $appointment)
    {
        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required'
        ]);

        $this->assertAppointmentSlotAllowed(
            $appointment->serviceRequest,
            $request->appointment_date,
            $request->appointment_time
        );

        $appointment->update([
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
        ]);

        Mail::to($appointment->serviceRequest->student->user->email)
            ->send(new AppointmentScheduledMail($appointment->serviceRequest));

        return redirect()
            ->back()
            ->with('success', 'Appointment rescheduled successfully.');
    }

    private function assertAppointmentSlotAllowed(ServiceRequest $serviceRequest, string $date, string $time): void
    {
        $appointmentDateTime = Carbon::parse($date . ' ' . $time, QueueBusinessCalendar::timezone());
        $faculty = optional($serviceRequest->student)->faculty;
        $campus = optional($serviceRequest->student)->campus;

        $holidayMessage = QueueBusinessCalendar::holidayReminderText($appointmentDateTime);
        if ($holidayMessage) {
            throw ValidationException::withMessages([
                'appointment_date' => $holidayMessage,
            ]);
        }

        if (QueueBusinessCalendar::isSabbath($appointmentDateTime)) {
            throw ValidationException::withMessages([
                'appointment_date' => 'Appointments are not available on Saturday (Sabbath).',
            ]);
        }

        if (!QueueBusinessCalendar::isOpenAt($appointmentDateTime, $serviceRequest->office_id, $faculty, $campus)) {
            throw ValidationException::withMessages([
                'appointment_time' => 'Appointment time is outside working hours. Allowed hours: ' . QueueBusinessCalendar::hoursDescription($serviceRequest->office_id, $faculty, $campus),
            ]);
        }
    }
}
