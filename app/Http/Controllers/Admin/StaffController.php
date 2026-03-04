<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        $staffs = Staff::with('user', 'office', 'subOffice')->get();
        $offices = Office::with('subOffices')->get();
        $officeSubOfficeMap = $offices->mapWithKeys(function ($office) {
            return [
                $office->id => $office->subOffices->map(function ($subOffice) {
                    return [
                        'id' => $subOffice->id,
                        'name' => $subOffice->name,
                    ];
                })->values(),
            ];
        });

        return view('admin.staff.index', compact('staffs', 'offices', 'officeSubOfficeMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'office_id' => 'nullable|exists:offices,id',
            'sub_office_id' => [
                'nullable',
                'integer',
                Rule::exists('office_sub_offices', 'id')->where(
                    fn ($query) => $query->where('office_id', $request->office_id)
                ),
            ],
            'campus' => 'nullable|string|max:255',
            'faculty' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $office = $request->filled('office_id')
            ? Office::find($request->office_id)
            : null;
        $isStudentAffairs = $office && str_contains(strtolower($office->name), 'student affairs');

        if ($isStudentAffairs) {
            $request->validate([
                'faculty' => 'required|string|max:255',
                'department' => 'required|string|max:255',
            ]);
        }

        // Create user
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'staff',
        ]);

        // Create staff record
        $staff = Staff::create([
            'name' => $request->name,
            'user_id' => $user->id,
            'office_id' => $request->office_id,
            'sub_office_id' => $request->sub_office_id,
            'campus' => $request->campus,
            'faculty' => $isStudentAffairs ? $request->faculty : null,
            'department' => $isStudentAffairs ? $request->department : null,
            'position' => $request->position,
            'phone' => $request->phone,
        ]);

        $user->update([
            'staff_number' => $staff->staff_number,
        ]);

        return redirect()->back()->with('success', 'Staff created successfully.');
    }

    public function update(Request $request, Staff $staff)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$staff->user_id}",
            'password' => 'nullable|string|min:6|confirmed',
            'office_id' => 'nullable|exists:offices,id',
            'sub_office_id' => [
                'nullable',
                'integer',
                Rule::exists('office_sub_offices', 'id')->where(
                    fn ($query) => $query->where('office_id', $request->office_id)
                ),
            ],
            'campus' => 'nullable|string|max:255',
            'faculty' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $office = $request->filled('office_id')
            ? Office::find($request->office_id)
            : null;
        $isStudentAffairs = $office && str_contains(strtolower($office->name), 'student affairs');

        if ($isStudentAffairs) {
            $request->validate([
                'faculty' => 'required|string|max:255',
                'department' => 'required|string|max:255',
            ]);
        }

        // Update user
        $user = $staff->user;
        $user->email = $request->email;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // Update staff details
        $staff->update([
            'name' => $request->name,
            'office_id' => $request->office_id,
            'sub_office_id' => $request->sub_office_id,
            'campus' => $request->campus,
            'faculty' => $isStudentAffairs ? $request->faculty : null,
            'department' => $isStudentAffairs ? $request->department : null,
            'position' => $request->position,
            'phone' => $request->phone,
        ]);

        return redirect()->back()->with('success', 'Staff updated successfully.');
    }

    public function destroy(Staff $staff)
    {
        $staff->user->delete(); // Will also delete staff record due to cascade
        return redirect()->back()->with('success', 'Staff deleted successfully.');
    }
}
