<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['student', 'staff'])->get();
        $offices = Office::all();
        return view('admin.users.index', compact('users', 'offices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:student,staff,admin',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validated['role'] === 'student') {
            $studentData = $request->validate([
                'name' => 'required|string|max:255',
                'student_number' => [
                    'required',
                    'string',
                    'unique:students,student_number',
                    'regex:/^\d{5}\/\d{4}$/',
                    function ($attribute, $value, $fail) {
                        $year = (int) explode('/', $value)[1];
                        $currentYear = (int) now()->format('Y');

                        if ($year < 1900 || $year > $currentYear) {
                            $fail('The student number year must be a valid 4-digit year and cannot be in the future.');
                        }
                    },
                ],
                'faculty' => 'required|string|max:255',
                'department' => 'required|string|max:255',
                'campus' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
            ]);
        }

        if ($validated['role'] === 'staff') {
            $staffData = $request->validate([
                'name' => 'required|string|max:255',
                'office_id' => 'nullable|exists:offices,id',
                'position' => 'nullable|string|max:255',
                'campus' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'faculty' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
            ]);
        }

        $user = User::create([
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        if ($validated['role'] === 'student') {
            $student = Student::create([
                'name' => $studentData['name'],
                'user_id' => $user->id,
                'student_number' => $studentData['student_number'],
                'faculty' => $studentData['faculty'],
                'department' => $studentData['department'],
                'campus' => $studentData['campus'],
                'phone' => $studentData['phone'],
            ]);

            $user->update([
                'student_number' => $student->student_number,
                'staff_number' => null,
            ]);
        }

        if ($validated['role'] === 'staff') {
            $office = filled($staffData['office_id'] ?? null) ? Office::find($staffData['office_id']) : null;
            $isStudentAffairs = $office && str_contains(strtolower($office->name), 'student affairs');

            if ($isStudentAffairs) {
                $request->validate([
                    'faculty' => 'required|string|max:255',
                    'department' => 'required|string|max:255',
                ]);
            }

            $staff = Staff::create([
                'name' => $staffData['name'],
                'user_id' => $user->id,
                'office_id' => $staffData['office_id'] ?? null,
                'position' => $staffData['position'] ?? null,
                'campus' => $staffData['campus'] ?? null,
                'phone' => $staffData['phone'] ?? null,
                'faculty' => $isStudentAffairs ? ($staffData['faculty'] ?? null) : null,
                'department' => $isStudentAffairs ? ($staffData['department'] ?? null) : null,
            ]);

            $user->update([
                'staff_number' => $staff->staff_number,
                'student_number' => null,
            ]);
        }

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'email' => "required|email|unique:users,email,{$user->id}",
            'role' => 'required|in:student,staff,admin',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->email = $validated['email'];
        $user->role = $validated['role'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if ($user->student) {
            $studentValidated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $user->student->update([
                'name' => $studentValidated['name'],
            ]);
            $user->update([
                'student_number' => $user->student->student_number,
            ]);
        }

        if ($user->staff) {
            $staffValidated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $user->staff->update([
                'name' => $staffValidated['name'],
            ]);
            $user->update([
                'staff_number' => $user->staff->staff_number,
            ]);
        }

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->back()->with('success', 'User deleted successfully.');
    }
    public function toggleStatus(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active
        ]);

        return back()->with('success', 'User status updated successfully.');
    }

    public function resetPassword(User $user)
    {
        $newPassword = Str::random(10);

        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        // Optional: email the new password
        Mail::raw(
            "Your password has been reset.\nNew Password: $newPassword",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Password Reset');
            }
        );

        return back()->with('success', 'Password reset successfully.');
    }

    public function verify(User $user)
    {
        $user->update([
            'email_verified_at' => now(),
        ]);

        return back()->with('success', 'User verified successfully.');
    }
}
