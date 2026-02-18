<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function edit()
    {
        $user = auth()->user();

        if ($user->student && $user->student->isProfileComplete()) {
            return redirect()->route('dashboard');
        }

        return view(
            'student.complete-profile',
            ['student' => $user->student]
        );
    }


    public function update(Request $request, $id)
    {
        $validated = $request->validate(
            [
                'faculty' => 'required|string|max:255',
                'department' => 'required|string|max:255',
                'campus' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
            ],
            [
                'faculty.required' => 'Please select a faculty.',
                'department.required' => 'Please select a department.',
                'campus.required' => 'Please select a campus.',
                'phone.required' => 'Please enter your phone number.',
            ]
        );

        $student = \App\Models\Student::where('user_id', $id)->firstOrFail();
        $student->update([
            'faculty' => $validated['faculty'],
            'department' => $validated['department'],
            'campus' => $validated['campus'],
            'phone' => $validated['phone'],
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Profile completed successfully.');
    }

    public function faq()
    {
        return view('student.faq.index');
    }
}
