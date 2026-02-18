<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $year = date('Y');

        $students = [
            [
                'name' => 'Eric Mugisha',
                'email' => 'eric.mugisha@student.university.edu',
                'faculty' => 'Faculty of Computing and Information Sciences',
                'department' => 'Information Technology',
                'campus' => 'Kigali Campus',
                'phone' => '0789001111',
            ],
            [
                'name' => 'Aline Uwase',
                'email' => 'aline.uwase@student.university.edu',
                'faculty' => 'Faculty of Computing and Information Sciences',
                'department' => 'Software Engineering',
                'campus' => 'Kigali Campus',
                'phone' => '0789002222',
            ],
            [
                'name' => 'Patrick Habimana',
                'email' => 'patrick.habimana@student.university.edu',
                'faculty' => 'Faculty of Computing and Information Sciences',
                'department' => 'Information Systems & Management',
                'campus' => 'Rwamagana Campus',
                'phone' => '0789003333',
            ],
            [
                'name' => 'Chantal Mukamana',
                'email' => 'chantal.mukamana@student.university.edu',
                'faculty' => 'Faculty of Economic Sciences & Management',
                'department' => 'Finance',
                'campus' => 'Nyanza Campus',
                'phone' => '0789004444',
            ],
            [
                'name' => 'Jean Paul Niyonzima',
                'email' => 'jeanpaul.niyonzima@student.university.edu',
                'faculty' => 'Faculty of Economic Sciences & Management',
                'department' => 'Economics',
                'campus' => 'Kigali Campus',
                'phone' => '0789005555',
            ],
        ];

        foreach ($students as $index => $data) {

            // 1️⃣ Create or update user
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'email_verified_at' => now(),
                ]
            );

            // 2️⃣ Generate student number (SEEDER ONLY)
            $studentNumber = str_pad($index + 1, 5, '0', STR_PAD_LEFT) . "/$year";

            // 3️⃣ Create or update student
            Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $data['name'],
                    'student_number' => $studentNumber,
                    'faculty' => $data['faculty'],
                    'department' => $data['department'],
                    'campus' => $data['campus'],
                    'phone' => $data['phone'],
                ]
            );

            $user->update([
                'student_number' => $studentNumber,
                'staff_number' => null,
            ]);
        }
    }
}
