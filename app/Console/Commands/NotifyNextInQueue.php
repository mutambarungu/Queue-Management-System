<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NotifyNextInQueue extends Command
{
    protected $signature = 'requests:notify-next';
    protected $description = 'Queue notification emails are temporarily disabled.';

    public function handle(): int
    {
        $this->info('Queue email notifications are disabled for now.');
        return self::SUCCESS;
    }
}
