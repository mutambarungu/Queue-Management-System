<?php

namespace Database\Seeders;

use App\Models\ServiceRequest;
use App\Models\Student;
use App\Models\Office;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ServiceRequestSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create();

        // Get all students, offices, and service types
        $students = Student::all();
        $offices = Office::all();

        if ($students->isEmpty() || $offices->isEmpty()) {
            $this->command->info("Please seed students and offices first.");
            return;
        }

        // Generate 50 test service requests
        for ($i = 0; $i < 50; $i++) {
            $student = $students->random();
            $office = $offices->random();
            $serviceType = $office->serviceTypes()->inRandomOrder()->first();

            // Skip if office has no service types
            if (!$serviceType) {
                continue;
            }

            // Random status
            $statusOptions = ['Submitted', 'In Review', 'Awaiting Student Response', 'Resolved', 'Closed'];
            $status = $faker->randomElement($statusOptions);

            // Random queued_at within past 7 days
            $queuedAt = $faker->dateTimeBetween('-7 days', 'now');

            // Random updated_at
            $updatedAt = $status === 'Resolved' || $status === 'Closed'
                ? $faker->dateTimeBetween($queuedAt, 'now')
                : null;

            // Create service request
            ServiceRequest::create([
                'student_id'      => $student->student_number,
                'office_id'       => $office->id,
                'service_type_id' => $serviceType->id,
                'description'     => $faker->sentence(10),
                'status'          => $status,
                'queued_at'       => $queuedAt,
                'archived_at'     => ($status === 'Resolved' && $faker->boolean(20)) ? Carbon::parse($updatedAt)->subDays(30) : null,
                'created_at'      => $queuedAt,
                'updated_at'      => $updatedAt ?? $queuedAt,
            ]);
        }

        $this->command->info("50 test service requests created successfully.");
    }
}
