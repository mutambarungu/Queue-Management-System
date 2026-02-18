<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('students', 'program')) {
            return;
        }

        // Preserve old data by moving program values into department when missing.
        if (Schema::hasColumn('students', 'department')) {
            DB::table('students')
                ->whereNull('department')
                ->whereNotNull('program')
                ->update(['department' => DB::raw('program')]);
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('program');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'program')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->string('program')->nullable();
        });
    }
};
