<?php

namespace App\Console\Commands;

use App\Models\ServiceRequest;
use App\Support\QueueTokenGenerator;
use Illuminate\Console\Command;

class BackfillQueueTokens extends Command
{
    protected $signature = 'requests:backfill-tokens';
    protected $description = 'Generate queue token codes for existing requests missing token values';

    public function handle(): int
    {
        $updated = 0;

        ServiceRequest::query()
            ->whereNull('token_number')
            ->orderBy('id')
            ->chunkById(100, function ($requests) use (&$updated) {
                foreach ($requests as $request) {
                    $token = QueueTokenGenerator::generate((int) $request->office_id, (int) $request->service_type_id);
                    $request->token_prefix = $token['token_prefix'];
                    $request->token_number = $token['token_number'];
                    $request->token_date = $token['token_date'];
                    $request->save();
                    $updated++;
                }
            });

        $this->info("Backfilled token codes for {$updated} request(s).");

        return self::SUCCESS;
    }
}
