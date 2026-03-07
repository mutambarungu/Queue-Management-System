<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceRequest;
use Carbon\Carbon;

class ArchiveOldServiceRequests extends Command
{
    protected $signature = 'requests:archive-old';
    protected $description = 'Archive resolved or closed requests older than 7 days';

    public function handle()
    {
        $count = ServiceRequest::whereIn('status', ['Resolved', 'Closed'])
            ->where('is_archived', false)
            ->where('updated_at', '<=', Carbon::now()->subDays(7))
            ->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);

        $this->info("Archived {$count} old service requests.");

        return Command::SUCCESS;
    }
}
