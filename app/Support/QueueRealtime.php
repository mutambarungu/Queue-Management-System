<?php

namespace App\Support;

use App\Events\QueueLaneUpdated;
use App\Events\ServiceRequestUpdatedRealtime;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

class QueueRealtime
{
    public static function enabled(): bool
    {
        return config('broadcasting.default') === 'reverb'
            && filled(config('broadcasting.connections.reverb.key'))
            && filled(config('broadcasting.connections.reverb.secret'))
            && filled(config('broadcasting.connections.reverb.app_id'));
    }

    public static function pushServiceRequestUpdate(ServiceRequest $serviceRequest): void
    {
        if (!self::enabled()) {
            return;
        }

        try {
            broadcast(new ServiceRequestUpdatedRealtime($serviceRequest));
        } catch (Throwable $exception) {
            Log::warning('Realtime broadcast failed for service request update', [
                'service_request_id' => $serviceRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public static function pushLaneUpdate(int $officeId, ?int $requestId = null): void
    {
        if (!self::enabled()) {
            return;
        }

        try {
            broadcast(new QueueLaneUpdated($officeId, $requestId));
        } catch (Throwable $exception) {
            Log::warning('Realtime broadcast failed for queue lane update', [
                'office_id' => $officeId,
                'service_request_id' => $requestId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
