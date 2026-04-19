<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Office;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use App\Models\ServiceType;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Support\QueueBusinessCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentCalendarDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_download_a_calendar_file_with_clear_details_and_reminders(): void
    {
        [$studentUser, $appointment] = $this->createStudentAppointment();

        $response = $this->actingAs($studentUser)
            ->get(route('student.appointments.calendar', $appointment));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=UTF-8');

        $content = $response->getContent();
        $unfoldedContent = str_replace("\r\n ", '', (string) $content);

        $this->assertNotFalse($content);
        $this->assertStringContainsString('BEGIN:VCALENDAR', $unfoldedContent);
        $this->assertStringContainsString('SUMMARY:Transcript Request Appointment - Registrar Office', $unfoldedContent);
        $this->assertStringContainsString('LOCATION:Admin Block 2\, Room 14', $unfoldedContent);
        $this->assertStringContainsString('Request ID: ' . $appointment->serviceRequest->request_number, $unfoldedContent);
        $this->assertStringContainsString('Staff note: Please bring your student ID card.', $unfoldedContent);
        $this->assertStringContainsString('X-WR-TIMEZONE:' . QueueBusinessCalendar::timezone(), $unfoldedContent);
        $this->assertStringContainsString('URL:' . route('student.appointments.show', $appointment), $unfoldedContent);
        $this->assertStringContainsString('TRIGGER:-P1D', $unfoldedContent);
        $this->assertStringContainsString('TRIGGER:-PT30M', $unfoldedContent);
        $this->assertStringContainsString('.ics', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_student_cannot_download_another_students_appointment_calendar(): void
    {
        [, $appointment] = $this->createStudentAppointment();

        $otherStudentUser = User::factory()->create([
            'role' => 'student',
        ]);

        Student::create([
            'student_number' => 'STU-2002',
            'name' => 'Other Student',
            'user_id' => $otherStudentUser->id,
            'faculty' => 'Business',
            'department' => 'Accounting',
            'campus' => 'Main',
            'phone' => '0780000002',
        ]);

        $this->actingAs($otherStudentUser)
            ->get(route('student.appointments.calendar', $appointment))
            ->assertForbidden();
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\Appointment}
     */
    private function createStudentAppointment(): array
    {
        $office = Office::create([
            'name' => 'Registrar Office',
        ]);

        $serviceType = ServiceType::create([
            'office_id' => $office->id,
            'name' => 'Transcript Request',
        ]);

        $studentUser = User::factory()->create([
            'role' => 'student',
        ]);

        $student = Student::create([
            'student_number' => 'STU-1001',
            'name' => 'Alice Student',
            'user_id' => $studentUser->id,
            'faculty' => 'Business',
            'department' => 'Finance',
            'campus' => 'Main',
            'phone' => '0780000001',
        ]);

        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $staff = Staff::create([
            'staff_number' => 'STF-1001',
            'name' => 'Grace Staff',
            'user_id' => $staffUser->id,
            'office_id' => $office->id,
            'position' => 'Officer',
            'phone' => '0780000003',
        ]);

        $serviceRequest = ServiceRequest::create([
            'request_number' => 'REQ-2026-0001',
            'student_id' => $student->student_number,
            'office_id' => $office->id,
            'service_type_id' => $serviceType->id,
            'request_mode' => 'appointment',
            'description' => 'Need an official transcript.',
            'status' => 'Appointment Scheduled',
            'token_prefix' => 'RG',
            'token_number' => 1,
            'token_date' => now()->toDateString(),
            'queue_stage' => 'waiting',
            'queued_at' => now(),
        ]);

        $reply = new ServiceRequestReply();
        $reply->service_request_id = $serviceRequest->id;
        $reply->user_id = $staffUser->id;
        $reply->message = 'Please bring your student ID card.';
        $reply->save();

        $appointment = Appointment::create([
            'service_request_id' => $serviceRequest->id,
            'appointment_date' => '2026-04-20',
            'appointment_time' => '10:00:00',
            'location' => 'Admin Block 2, Room 14',
            'staff_number' => $staff->staff_number,
        ]);

        return [$studentUser, $appointment];
    }
}
