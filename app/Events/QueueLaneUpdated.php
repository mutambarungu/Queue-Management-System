<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueLaneUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $officeId,
        public ?int $requestId = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('office.' . $this->officeId)];
    }

    public function broadcastAs(): string
    {
        return 'queue.lane.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'office_id' => $this->officeId,
            'request_id' => $this->requestId,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
