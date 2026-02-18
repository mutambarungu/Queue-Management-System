<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('students', 'id')) {
            return;
        }

        // If this unique index exists from earlier migration, drop it first.
        try {
            DB::statement('ALTER TABLE students DROP INDEX students_id_unique');
        } catch (\Throwable $e) {
            // Ignore when index does not exist.
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'id')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->nullable()->unique()->first();
        });
    }
};
