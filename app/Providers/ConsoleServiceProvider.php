<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\ArchiveOldServiceRequests;
use App\Console\Commands\BackfillQueueTokens;
use App\Console\Commands\ProcessQueueCalls;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            ArchiveOldServiceRequests::class,
            BackfillQueueTokens::class,
            ProcessQueueCalls::class,
        ]);
    }

    public function boot()
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('requests:archive-old')->daily();
            $schedule->command('queue:process-calls')->everyMinute();
        });
    }
}
