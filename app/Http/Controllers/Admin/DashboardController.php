<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\Staff;
use App\Models\ServiceRequest;
use App\Models\Office;
use App\Models\Faculty;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | 1. Enforce Student Profile Completion (students only)
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'student') {
            if (! $user->student || ! $user->student->isProfileComplete()) {
                return redirect()->route('student.profile.complete');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Base Query (Role Aware)
        |--------------------------------------------------------------------------
        */
        $requestsQuery = ServiceRequest::query();

        switch ($user->role) {

            case 'admin':
                // See everything
                break;

            case 'staff':
                // Only requests for staff office
                $requestsQuery->where('office_id', $user->staff->office->id);
                break;

            case 'student':
                // Only own requests
                $requestsQuery->where('student_id', $user->student->student_number);
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Stats
        |--------------------------------------------------------------------------
        */
        $totalRequests = (clone $requestsQuery)->count();

        $pendingRequests = (clone $requestsQuery)
            ->whereIn('status', [
                'Submitted',
                'In Review',
                'Awaiting Student Response'
            ])
            ->count();

        $resolvedRequests = (clone $requestsQuery)
            ->where('status', 'Resolved')
            ->count();

        $appointmentRequired = (clone $requestsQuery)
            ->where('status', 'Appointment Scheduled')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 4. Graph Data
        |--------------------------------------------------------------------------
        */

        // Requests per office
        $requestsPerOffice = Office::withCount([
            'requests' => function ($query) use ($user) {

                if ($user->role === 'student') {
                    $query->where('student_id', $user->student->student_number);
                }

                if ($user->role === 'staff') {
                    $query->where('office_id', $user->staff->office->id);
                }
            }
        ])->get();

        // Requests per status
        $requestsPerStatus = (clone $requestsQuery)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('dashboard', compact(
            'totalRequests',
            'pendingRequests',
            'resolvedRequests',
            'appointmentRequired',
            'requestsPerOffice',
            'requestsPerStatus'
        ));
    }
}
