<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'student_number' => [
                'required',
                'string',
                'max:50',
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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($request, &$user) {

            // 1. Create User
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // 2. Auto-create Student linked to User
            $student = Student::create([
                'name'           => $request->name,
                'user_id'        => $user->id,
                'student_number' => $request->student_number, // or leave null to auto-generate
                'phone'          => null,
            ]);

            $user->update([
                'student_number' => $student->student_number,
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
