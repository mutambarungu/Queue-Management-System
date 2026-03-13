<?php

namespace App\Http\Controllers;

use App\Models\RequestAttachment;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function request(RequestAttachment $attachment)
    {
        $attachment->loadMissing('request.serviceType', 'request.student');
        $serviceRequest = $attachment->request;

        if (!$serviceRequest) {
            abort(404);
        }

        $this->authorizeRequestAccess($serviceRequest);

        return $this->serveAttachment($attachment->file_path, $attachment->file_name);
    }

    public function reply(ServiceRequestReply $reply)
    {
        $reply->loadMissing('serviceRequest.serviceType', 'serviceRequest.student');
        $serviceRequest = $reply->serviceRequest;

        if (!$serviceRequest || !$reply->attachment) {
            abort(404);
        }

        $this->authorizeRequestAccess($serviceRequest);

        return $this->serveAttachment($reply->attachment, basename($reply->attachment));
    }

    private function serveAttachment(?string $path, ?string $filename = null)
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($path);
        $mime = Storage::disk('public')->mimeType($path) ?? 'application/octet-stream';
        $safeName = $filename ?: basename($path);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($safeName) . '"',
        ]);
    }

    private function authorizeRequestAccess(ServiceRequest $serviceRequest): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isStudent()) {
            $student = $user->student;
            if (!$student || (string) $serviceRequest->student_id !== (string) $student->student_number) {
                abort(403);
            }
            return;
        }

        if ($user->isStaff()) {
            $staff = $user->staff;
            if (!$staff) {
                abort(403);
            }

            if ((int) $staff->office_id !== (int) $serviceRequest->office_id) {
                abort(403);
            }

            $serviceSubOfficeId = optional($serviceRequest->serviceType)->sub_office_id;
            if (filled($staff->sub_office_id)) {
                if ((int) $staff->sub_office_id !== (int) $serviceSubOfficeId) {
                    abort(403);
                }
            } else {
                if (filled($serviceSubOfficeId)) {
                    abort(403);
                }
            }

            $student = $serviceRequest->student;
            if ($student) {
                if (filled($staff->campus) && $student->campus !== $staff->campus) {
                    abort(403);
                }
                if (filled($staff->faculty) && $student->faculty !== $staff->faculty) {
                    abort(403);
                }
                if (filled($staff->department) && $student->department !== $staff->department) {
                    abort(403);
                }
            }
            return;
        }

        abort(403);
    }
}
