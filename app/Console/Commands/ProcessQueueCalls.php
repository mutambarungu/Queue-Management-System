<?php

namespace App\Console\Commands;

use App\Models\ServiceRequest;
use App\Support\QueueBusinessCalendar;
use Illuminate\Console\Command;

class ProcessQueueCalls extends Command
{
    protected $signature = 'queue:process-calls';
    protected $description = 'Process queue call timeouts: recall once, then mark no-show';

    public function handle(): int
    {
        $now = QueueBusinessCalendar::now();
        $updated = 0;

        $calledRequests = ServiceRequest::query()
            ->with('serviceType')
            ->whereNull('archived_at')
            ->where('queue_stage', 'called')
            ->whereNotNull('called_at')
            ->get();

        foreach ($calledRequests as $request) {
            $policy = QueueBusinessCalendar::lanePolicyFor(
                (int) $request->office_id,
                (int) optional($request->serviceType)->sub_office_id ?: null
            );
            $timeoutSeconds = (int) ($policy['recall_timeout_seconds'] ?? 90);
            $secondsElapsed = $request->called_at?->diffInSeconds($now) ?? 0;

            if ((int) $request->recall_count < 1 && $secondsElapsed >= $timeoutSeconds) {
                $request->recall_count = 1;
                $request->recalled_at = $now;
                $request->called_at = $now;
                $request->save();
                $updated++;
                continue;
            }

            if ((int) $request->recall_count >= 1 && $secondsElapsed >= $timeoutSeconds) {
                $request->queue_stage = 'no_show';
                $request->no_show_at = $now;
                $request->status = 'Closed';
                $request->save();
                $updated++;
            }
        }

        $this->info("Processed {$updated} queue call timeout update(s).");

        return self::SUCCESS;
    }
}
