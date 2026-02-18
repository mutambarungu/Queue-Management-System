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
        if (!Schema::hasColumn('offices', 'parent_office_id')) {
            return;
        }

        Schema::table('offices', function (Blueprint $table) {
            $table->dropForeign(['parent_office_id']);
            $table->dropColumn('parent_office_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('offices', 'parent_office_id')) {
            return;
        }

        Schema::table('offices', function (Blueprint $table) {
            $table->foreignId('parent_office_id')
                ->nullable()
                ->after('description')
                ->constrained('offices')
                ->nullOnDelete();
        });
    }
};

