<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_position_uses_request_id_as_tie_breaker_for_same_second_entries(): void
    {
        [$firstRequest, $secondRequest] = $this->createSameSecondWalkIns();

        $this->assertSame(1, $firstRequest->fresh()->queue_position);
        $this->assertSame(2, $secondRequest->fresh()->queue_position);
    }

    public function test_staff_calls_oldest_waiting_walk_in_first_when_queue_times_match(): void
    {
        [$firstRequest, $secondRequest, $staffUser] = $this->createSameSecondWalkIns(withStaff: true);

        $this->actingAs($staffUser)
            ->post(route('staff.queue.call-next'))
            ->assertSessionHas('success');

        $this->assertSame('called', $firstRequest->fresh()->queue_stage);
        $this->assertSame('waiting', $secondRequest->fresh()->queue_stage);
    }

    /**
     * @return array{0: \App\Models\ServiceRequest, 1: \App\Models\ServiceRequest, 2?: \App\Models\User}
     */
    private function createSameSecondWalkIns(bool $withStaff = false): array
    {
        $office = Office::create([
            'name' => 'Admissions Office',
        ]);

        $serviceType = ServiceType::create([
            'office_id' => $office->id,
            'name' => 'General Queue',
        ]);

        $firstStudent = $this->createStudent('STU-3001', 'first@example.com', 'First Student');
        $secondStudent = $this->createStudent('STU-3002', 'second@example.com', 'Second Student');
        $queuedAt = '2026-04-19 09:00:00';

        $firstRequest = ServiceRequest::create([
            'student_id' => $firstStudent->student_number,
            'office_id' => $office->id,
            'service_type_id' => $serviceType->id,
            'request_mode' => 'walk_in',
            'description' => 'First walk-in',
            'status' => 'Submitted',
            'queue_stage' => 'waiting',
            'queued_at' => $queuedAt,
        ]);

        $secondRequest = ServiceRequest::create([
            'student_id' => $secondStudent->student_number,
            'office_id' => $office->id,
            'service_type_id' => $serviceType->id,
            'request_mode' => 'walk_in',
            'description' => 'Second walk-in',
            'status' => 'Submitted',
            'queue_stage' => 'waiting',
            'queued_at' => $queuedAt,
        ]);

        if (!$withStaff) {
            return [$firstRequest, $secondRequest];
        }

        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        Staff::create([
            'staff_number' => 'STF-3001',
            'name' => 'Queue Officer',
            'user_id' => $staffUser->id,
            'office_id' => $office->id,
            'position' => 'Officer',
            'phone' => '0780000300',
        ]);

        return [$firstRequest, $secondRequest, $staffUser];
    }

    private function createStudent(string $studentNumber, string $email, string $name): Student
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => 'student',
        ]);

        return Student::create([
            'student_number' => $studentNumber,
            'name' => $name,
            'user_id' => $user->id,
            'faculty' => 'Business',
            'department' => 'Management',
            'campus' => 'Main',
            'phone' => '0780000100',
        ]);
    }
}
