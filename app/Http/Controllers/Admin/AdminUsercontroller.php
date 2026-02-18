<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminUsercontroller extends Controller
{
    public function index()
    {
        return inertia('Admin/Users/Index', [
            'users' => User::where('role', '!=', 'student')->get(),
        ]);
    }
    public function create()
    {
        return inertia('Admin/Users/Create');
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string',
            'email' => 'required|email|unique:users',
            'role' => 'required|in:staff,admin',
        ]);

        User::create([
            'email' => $request->email,
            'role' => $request->role,
            'password' => bcrypt(Str::random(10)), // temporary password
        ]);

        return redirect('/admin/users')
            ->with('success', 'User created successfully');
    }
    public function toggleStatus(User $user)
    {
        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return back();
    }
}
