<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['user'])->get();
        return view('admin.students.index', compact('students'));
    }

    // Store new student
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'faculty'  => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'campus' => 'required|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'student_number' => [
                'nullable',
                'string',
                'unique:students,student_number',
                'regex:/^\d{5}\/\d{4}$/',
                function ($attribute, $value, $fail) {
                    if (blank($value)) {
                        return;
                    }

                    $year = (int) explode('/', $value)[1];
                    $currentYear = (int) now()->format('Y');

                    if ($year < 1900 || $year > $currentYear) {
                        $fail('The student number year must be a valid 4-digit year and cannot be in the future.');
                    }
                },
            ],
        ]);

        // 1️⃣ Create User
        $user = User::create([
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'student', // ensure student role
        ]);

        // 2️⃣ Create Student linked to user
        $student = Student::create([
            'name'           => $request->name,
            'user_id'        => $user->id,
            'faculty'        => $request->faculty,
            'department'     => $request->department,
            'campus'         => $request->campus,
            'phone'          => $request->phone,
            'student_number' => $request->student_number,
        ]);

        $user->update([
            'student_number' => $student->student_number,
        ]);

        return redirect()->back()->with('success', 'Student created successfully.');
    }

    // Update existing student
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $student->user_id,
            'password' => 'nullable|string|min:6|confirmed',
            'faculty'  => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'campus' => 'required|string|max:255',
            'phone'    => 'nullable|string|max:20',
        ]);

        // Update User (email/password only)
        $userData = [
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $student->user->update($userData);

        // Update Student
        $student->update([
            'name' => $request->name,
            'faculty' => $request->faculty,
            'department' => $request->department,
            'campus' => $request->campus,
            'phone'   => $request->phone,
        ]);

        return redirect()->back()->with('success', 'Student updated successfully.');
    }

    // Destroy student
    public function destroy(Student $student)
    {
        // Optional: delete linked user
        $student->user->delete();
        $student->delete();

        return redirect()->back()->with('success', 'Student deleted successfully.');
    }
}
