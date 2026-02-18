<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

        // Backfill names from users to profile tables before dropping users.name.
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

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'student_number')) {
                $table->string('student_number')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'staff_number')) {
                $table->string('staff_number')->nullable()->after('student_number');
            }
        });

        DB::statement(
            'UPDATE users u
             LEFT JOIN students s ON s.user_id = u.id
             LEFT JOIN staff st ON st.user_id = u.id
             SET u.student_number = s.student_number,
                 u.staff_number = st.staff_number'
        );

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('student_number')
                ->references('student_number')
                ->on('students')
                ->nullOnDelete();

            $table->foreign('staff_number')
                ->references('staff_number')
                ->on('staff')
                ->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
        });

        DB::statement(
            'UPDATE users u
             LEFT JOIN students s ON s.user_id = u.id
             LEFT JOIN staff st ON st.user_id = u.id
             SET u.name = COALESCE(s.name, st.name, u.email)'
        );

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'student_number')) {
                $table->dropForeign(['student_number']);
                $table->dropColumn('student_number');
            }
            if (Schema::hasColumn('users', 'staff_number')) {
                $table->dropForeign(['staff_number']);
                $table->dropColumn('staff_number');
            }
        });

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
