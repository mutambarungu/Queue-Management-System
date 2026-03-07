<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('counter_number', 20)->nullable()->after('sub_office_id');
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('queue_stage', 30)->default('waiting')->after('priority');
            $table->timestamp('called_at')->nullable()->after('queue_stage');
            $table->timestamp('recalled_at')->nullable()->after('called_at');
            $table->timestamp('no_show_at')->nullable()->after('recalled_at');
            $table->unsignedTinyInteger('recall_count')->default(0)->after('no_show_at');
            $table->string('serving_counter', 20)->nullable()->after('recall_count');
        });

        Schema::table('queue_calendar_settings', function (Blueprint $table) {
            $table->json('lane_policies')->nullable()->after('special_rules');
        });
    }

    public function down(): void
    {
        Schema::table('queue_calendar_settings', function (Blueprint $table) {
            $table->dropColumn('lane_policies');
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn([
                'queue_stage',
                'called_at',
                'recalled_at',
                'no_show_at',
                'recall_count',
                'serving_counter',
            ]);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('counter_number');
        });
    }
};
