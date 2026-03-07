<?php

namespace App\Observers;

use App\Models\ServiceRequest;
use App\Support\QueueRealtime;

class ServiceRequestObserver
{
    public function created(ServiceRequest $serviceRequest): void
    {
        QueueRealtime::pushLaneUpdate((int) $serviceRequest->office_id, (int) $serviceRequest->id);
        QueueRealtime::pushServiceRequestUpdate($serviceRequest);
    }

    public function updated(ServiceRequest $serviceRequest): void
    {
        if (!$this->hasRealtimeRelevantChanges($serviceRequest)) {
            return;
        }

        $currentOfficeId = (int) $serviceRequest->office_id;
        $previousOfficeId = (int) ($serviceRequest->getOriginal('office_id') ?? $currentOfficeId);

        QueueRealtime::pushLaneUpdate($currentOfficeId, (int) $serviceRequest->id);
        if ($previousOfficeId !== $currentOfficeId) {
            QueueRealtime::pushLaneUpdate($previousOfficeId, (int) $serviceRequest->id);
        }

        QueueRealtime::pushServiceRequestUpdate($serviceRequest->fresh(['office', 'serviceType.subOffice', 'student']));
    }

    private function hasRealtimeRelevantChanges(ServiceRequest $serviceRequest): bool
    {
        return $serviceRequest->wasChanged([
            'status',
            'office_id',
            'service_type_id',
            'queued_at',
            'archived_at',
            'is_archived',
            'updated_at',
        ]);
    }
}
