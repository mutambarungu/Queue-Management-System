<?php

use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('student.request.{requestId}', function ($user, int $requestId) {
    if ($user->role === 'admin') {
        return true;
    }

    $serviceRequest = ServiceRequest::with(['serviceType', 'student'])->find($requestId);
    if (!$serviceRequest) {
        return false;
    }

    if ($user->role === 'student') {
        return optional($serviceRequest->student)->user_id === $user->id;
    }

    if ($user->role === 'staff') {
        $staff = $user->staff;
        if (!$staff) {
            return false;
        }

        if ((int) $serviceRequest->office_id !== (int) $staff->office_id) {
            return false;
        }

        if (filled($staff->sub_office_id) && (int) optional($serviceRequest->serviceType)->sub_office_id !== (int) $staff->sub_office_id) {
            return false;
        }

        if (filled($staff->campus) && optional($serviceRequest->student)->campus !== $staff->campus) {
            return false;
        }

        if (filled($staff->faculty) && optional($serviceRequest->student)->faculty !== $staff->faculty) {
            return false;
        }

        if (filled($staff->department) && optional($serviceRequest->student)->department !== $staff->department) {
            return false;
        }

        return true;
    }

    return false;
});
