<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('queue_calendar_settings')) {
            return;
        }

        Schema::table('queue_calendar_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('queue_calendar_settings', 'special_rules')) {
                $table->json('special_rules')->nullable()->after('holidays');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('queue_calendar_settings')) {
            return;
        }

        if (!Schema::hasColumn('queue_calendar_settings', 'special_rules')) {
            return;
        }

        Schema::table('queue_calendar_settings', function (Blueprint $table) {
            $table->dropColumn('special_rules');
        });
    }
};
