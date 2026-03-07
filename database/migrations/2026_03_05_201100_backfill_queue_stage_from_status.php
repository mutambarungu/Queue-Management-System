<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_requests')
            ->whereIn('status', ['Resolved', 'Closed'])
            ->update([
                'queue_stage' => DB::raw("CASE WHEN no_show_at IS NOT NULL THEN 'no_show' ELSE 'completed' END"),
                'called_at' => null,
                'recalled_at' => null,
                'recall_count' => 0,
                'serving_counter' => null,
            ]);

        DB::table('service_requests')
            ->where('status', 'In Review')
            ->update([
                'queue_stage' => 'serving',
            ]);

        DB::table('service_requests')
            ->whereIn('status', ['Submitted', 'Awaiting Student Response', 'Appointment Scheduled', 'Appointment Required'])
            ->update([
                'queue_stage' => 'waiting',
                'called_at' => null,
                'recalled_at' => null,
                'no_show_at' => null,
                'recall_count' => 0,
                'serving_counter' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('service_requests')->update([
            'queue_stage' => 'waiting',
            'called_at' => null,
            'recalled_at' => null,
            'no_show_at' => null,
            'recall_count' => 0,
            'serving_counter' => null,
        ]);
    }
};
