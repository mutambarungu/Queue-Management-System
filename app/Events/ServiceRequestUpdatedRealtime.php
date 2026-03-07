<?php

namespace App\Events;

use App\Models\ServiceRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestUpdatedRealtime implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public ServiceRequest $serviceRequest)
    {
        $this->serviceRequest->loadMissing(['office', 'serviceType.subOffice', 'student']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('student.request.' . $this->serviceRequest->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'service-request.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'request_id' => $this->serviceRequest->id,
            'office_id' => $this->serviceRequest->office_id,
            'status' => $this->serviceRequest->status,
            'queue_position' => $this->serviceRequest->queue_position,
            'people_ahead' => $this->serviceRequest->people_ahead,
            'currently_serving' => optional($this->serviceRequest->currently_serving)->queue_position,
            'next_in_line' => optional($this->serviceRequest->next_in_line)->queue_position,
            'queue_state' => $this->serviceRequest->queue_state,
            'lane_label' => optional(optional($this->serviceRequest->serviceType)->subOffice)->name ?: 'General Queue',
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
