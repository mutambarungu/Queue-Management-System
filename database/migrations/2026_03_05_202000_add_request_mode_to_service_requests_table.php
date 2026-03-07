<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('request_mode', 20)->default('online')->after('service_type_id');
            $table->index(['office_id', 'request_mode', 'queue_stage'], 'service_requests_queue_mode_stage_idx');
        });

        DB::table('service_requests')
            ->where('status', 'Appointment Scheduled')
            ->update(['request_mode' => 'appointment']);
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex('service_requests_queue_mode_stage_idx');
            $table->dropColumn('request_mode');
        });
    }
};
