<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'name')) {
                $table->string('name')->nullable()->after('student_number');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'name')) {
                $table->string('name')->nullable()->after('staff_number');
            }
        });

        if (Schema::hasColumn('users', 'name')) {
            DB::statement(
                'UPDATE students s
                 JOIN users u ON u.id = s.user_id
                 SET s.name = u.name
                 WHERE s.name IS NULL'
            );

            DB::statement(
                'UPDATE staff st
                 JOIN users u ON u.id = st.user_id
                 SET st.name = u.name
                 WHERE st.name IS NULL'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'name')) {
                $table->dropColumn('name');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};

