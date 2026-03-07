<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Office;
use App\Models\ServiceRequest;
use App\Models\Staff;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

// Quick commands:
// php artisan migrate:fresh --seed --seeder=AdminSeeder
// php artisan db:seed --class=BusyDayQueueDemoSeeder
class BusyDayQueueDemoSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::query()->get();
        $offices = Office::query()->with('serviceTypes')->get();

        if ($students->isEmpty() || $offices->isEmpty()) {
            $this->command?->warn('Busy day demo seeder skipped: students or offices are missing.');
            return;
        }

        // Idempotent reruns: clear previous generated demo rows only.
        ServiceRequest::query()
            ->where('description', 'like', '[DEMO-BUSY-DAY]%')
            ->delete();

        $today = now('Africa/Kigali')->toDateString();
        $created = 0;

        foreach ($offices as $office) {
            if ($office->serviceTypes->isEmpty()) {
                continue;
            }

            $staff = Staff::query()
                ->where('office_id', $office->id)
                ->inRandomOrder()
                ->first();

            $counter = $staff?->counter_number ?: 'Counter ' . rand(1, 6);
            $serviceTypes = $office->serviceTypes->values();

            $created += $this->seedServing($students, $serviceTypes, $office->id, $counter, $today);
            $created += $this->seedWaitingWalkIns($students, $serviceTypes, $office->id);
            $created += $this->seedWaitingAppointments($students, $serviceTypes, $office->id, $staff?->staff_number, $today);
            $created += $this->seedRecentHistory($students, $serviceTypes, $office->id, $counter, $today);
        }

        $this->command?->info("Busy day demo seeded: {$created} queue requests created.");
    }

    private function seedServing(Collection $students, Collection $serviceTypes, int $officeId, string $counter, string $today): int
    {
        $now = Carbon::now('Africa/Kigali');

        // One called walk-in.
        $called = $this->createRequest([
            'student_id' => $students->random()->student_number,
            'office_id' => $officeId,
            'service_type_id' => $serviceTypes->random()->id,
            'request_mode' => 'walk_in',
            'description' => '[DEMO-BUSY-DAY] Walk-in currently being called.',
            'status' => 'Submitted',
            'queue_stage' => 'called',
            'queued_at' => $now->copy()->subMinutes(rand(25, 70)),
            'called_at' => $now->copy()->subMinutes(rand(1, 4)),
            'serving_counter' => $counter,
        ], $now->copy()->subMinutes(rand(25, 70)), $now->copy()->subMinutes(rand(1, 2)));

        // One serving appointment.
        $serving = $this->createRequest([
            'student_id' => $students->random()->student_number,
            'office_id' => $officeId,
            'service_type_id' => $serviceTypes->random()->id,
            'request_mode' => 'appointment',
            'description' => '[DEMO-BUSY-DAY] Appointment actively being served.',
            'status' => 'In Review',
            'queue_stage' => 'serving',
            'queued_at' => $now->copy()->subMinutes(rand(40, 120)),
            'called_at' => $now->copy()->subMinutes(rand(5, 12)),
            'serving_counter' => $counter,
        ], $now->copy()->subMinutes(rand(40, 120)), $now->copy()->subMinutes(rand(3, 6)));

        Appointment::query()->create([
            'service_request_id' => $serving->id,
            'appointment_date' => $today,
            'appointment_time' => $now->copy()->subMinutes(rand(5, 12))->format('H:i:s'),
            'location' => $counter,
            'status' => 'Scheduled',
            'staff_number' => null,
        ]);

        return 2;
    }

    private function seedWaitingWalkIns(Collection $students, Collection $serviceTypes, int $officeId): int
    {
        $now = Carbon::now('Africa/Kigali');
        $count = rand(12, 18);

        for ($i = 0; $i < $count; $i++) {
            $queuedAt = $now->copy()->subMinutes(rand(15, 240));
            $this->createRequest([
                'student_id' => $students->random()->student_number,
                'office_id' => $officeId,
                'service_type_id' => $serviceTypes->random()->id,
                'request_mode' => 'walk_in',
                'description' => '[DEMO-BUSY-DAY] Walk-in waiting in queue.',
                'status' => fake()->boolean(20) ? 'Awaiting Student Response' : 'Submitted',
                'queue_stage' => 'waiting',
                'queued_at' => $queuedAt,
            ], $queuedAt->copy()->subMinutes(rand(4, 20)), $queuedAt);
        }

        return $count;
    }

    private function seedWaitingAppointments(Collection $students, Collection $serviceTypes, int $officeId, ?string $staffNumber, string $today): int
    {
        $now = Carbon::now('Africa/Kigali');
        $count = rand(8, 12);

        for ($i = 0; $i < $count; $i++) {
            $queuedAt = $now->copy()->subMinutes(rand(20, 300));
            $request = $this->createRequest([
                'student_id' => $students->random()->student_number,
                'office_id' => $officeId,
                'service_type_id' => $serviceTypes->random()->id,
                'request_mode' => 'appointment',
                'description' => '[DEMO-BUSY-DAY] Appointment waiting for service.',
                'status' => 'Appointment Scheduled',
                'queue_stage' => 'waiting',
                'queued_at' => $queuedAt,
            ], $queuedAt->copy()->subMinutes(rand(10, 40)), $queuedAt);

            Appointment::query()->create([
                'service_request_id' => $request->id,
                'appointment_date' => $today,
                'appointment_time' => $now->copy()->addMinutes(rand(10, 240))->format('H:i:s'),
                'location' => 'Main Service Desk',
                'status' => 'Scheduled',
                'staff_number' => $staffNumber,
            ]);
        }

        return $count;
    }

    private function seedRecentHistory(Collection $students, Collection $serviceTypes, int $officeId, string $counter, string $today): int
    {
        $now = Carbon::now('Africa/Kigali');
        $count = rand(10, 16);

        for ($i = 0; $i < $count; $i++) {
            $isNoShow = fake()->boolean(25);
            $mode = fake()->boolean(45) ? 'appointment' : 'walk_in';
            $queuedAt = $now->copy()->subMinutes(rand(90, 540));
            $calledAt = $queuedAt->copy()->addMinutes(rand(10, 40));
            $updatedAt = $calledAt->copy()->addMinutes(rand(5, 30));

            $request = $this->createRequest([
                'student_id' => $students->random()->student_number,
                'office_id' => $officeId,
                'service_type_id' => $serviceTypes->random()->id,
                'request_mode' => $mode,
                'description' => '[DEMO-BUSY-DAY] Recently processed token for activity feed.',
                'status' => $isNoShow ? 'Closed' : 'Resolved',
                'queue_stage' => $isNoShow ? 'no_show' : 'completed',
                'queued_at' => $queuedAt,
                'called_at' => $calledAt,
                'no_show_at' => $isNoShow ? $updatedAt : null,
                'serving_counter' => $counter,
            ], $queuedAt->copy()->subMinutes(rand(5, 25)), $updatedAt);

            if ($mode === 'appointment') {
                Appointment::query()->create([
                    'service_request_id' => $request->id,
                    'appointment_date' => $today,
                    'appointment_time' => $calledAt->format('H:i:s'),
                    'location' => $counter,
                    'status' => $isNoShow ? 'Cancelled' : 'Completed',
                    'staff_number' => null,
                ]);
            }
        }

        return $count;
    }

    private function createRequest(array $attributes, Carbon $createdAt, Carbon $updatedAt): ServiceRequest
    {
        $request = ServiceRequest::query()->create($attributes);
        $request->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ])->saveQuietly();

        return $request;
    }
}



// php artisan db:seed --class=BusyDayQueueDemoSeeder