<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\ServiceRequest;
use App\Models\Office;
use Illuminate\Database\Eloquent\Builder;

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
                // Only requests within staff scope
                $this->applyStaffScope($requestsQuery, $user->staff);
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
        $requestsPerOfficeQuery = Office::query();

        if ($user->role === 'staff' && filled(optional($user->staff)->office_id)) {
            $requestsPerOfficeQuery->whereKey($user->staff->office_id);
        }

        $requestsPerOffice = $requestsPerOfficeQuery->withCount([
            'requests' => function ($query) use ($user) {

                if ($user->role === 'student') {
                    $query->where('student_id', $user->student->student_number);
                }

                if ($user->role === 'staff') {
                    $this->applyStaffScope($query, $user->staff);
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

    private function applyStaffScope(Builder $query, Staff $staff): void
    {
        if (filled($staff->office_id)) {
            $query->where('office_id', $staff->office_id);
        }

        $query->whereHas('serviceType', function ($serviceTypeQuery) use ($staff) {
            if (filled($staff->sub_office_id)) {
                $serviceTypeQuery->where('sub_office_id', $staff->sub_office_id);
            } else {
                $serviceTypeQuery->whereNull('sub_office_id');
            }
        });

        $query->whereHas('student', function ($studentQuery) use ($staff) {
            if (filled($staff->campus)) {
                $studentQuery->where('campus', $staff->campus);
            }

            if (filled($staff->faculty)) {
                $studentQuery->where('faculty', $staff->faculty);
            }

            if (filled($staff->department)) {
                $studentQuery->where('department', $staff->department);
            }
        });
    }
}
