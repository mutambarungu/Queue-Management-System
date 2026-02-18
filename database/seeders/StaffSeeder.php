<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Staff;
use App\Models\Office;
use App\Models\Faculty;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch offices
        $finance   = Office::where('name', 'Finance')->first();
        $registrar = Office::where('name', 'Registrar')->first();
        $it        = Office::where('name', 'Information Technology')->first();
        $hod       = Office::where('name', 'Head of Department (HOD)')->first();
        $dean      = Office::where('name', 'Dean Office')->first();
        

        $staffMembers = [
            [
                'name' => 'Dr. Jean Claude Niyonzima',
                'email' => 'jc.niyonzima@unilak.com',
                'role' => 'staff',
                'office_id' => $dean?->id,
                'position' => 'Dean of Faculty of IT',
                'phone' => '0788123456',
            ],
            [
                'name' => 'Ms. Alice Uwimana',
                'email' => 'alice.uwimana@unilak.com',
                'role' => 'staff',
                'office_id' => $finance?->id,
                'position' => 'Finance Officer',
                'phone' => '0788234567',
            ],
            [
                'name' => 'Mr. Patrick Habimana',
                'email' => 'patrick.habimana@unilak.com',
                'role' => 'staff',
                'office_id' => $registrar?->id,
                'position' => 'Registrar Officer',
                'phone' => '0788345678',
            ],
            [
                'name' => 'Eng. Samuel Mugabo',
                'email' => 'samuel.mugabo@unilak.com',
                'role' => 'staff',
                'office_id' => $it?->id,
                'position' => 'IT Support Lead',
                'phone' => '0788456789',
            ],
            [
                'name' => 'Dr. Grace Mukamana',
                'email' => 'grace.mukamana@unilak.edu',
                'role' => 'staff',
                'office_id' => $hod?->id,
                'position' => 'Head of Department – Engineering',
                'phone' => '0788567890',
            ],
            [
                'name' => 'Mr. Emmanuel Nkurunziza',
                'email' => 'emmanuel.nkurunziza@unilak.com',
                'role' => 'staff',
                'office_id' => $hod?->id,
                'position' => 'Head of Department – Business',
                'phone' => '0788678901',
            ],
        ];

        foreach ($staffMembers as $data) {

            // 1️⃣ Create or fetch user
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'password' => Hash::make('password'),
                    'role' => $data['role'],
                    'email_verified_at' => now(),
                ]
            );

            // 2️⃣ Create staff record
            Staff::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'staff_number' => 'STF-' . date('Y') . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                    'name' => $data['name'],
                    'office_id' => $data['office_id'],
                    'position' => $data['position'],
                    'phone' => $data['phone'],
                ]
            );

            $user->update([
                'staff_number' => $user->staff?->staff_number,
                'student_number' => null,
            ]);
        }
    }
}
