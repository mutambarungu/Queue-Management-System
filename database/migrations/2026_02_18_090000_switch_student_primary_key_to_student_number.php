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
            // Already using student_number-only key design.
            return;
        }

        // 1) Move service_requests.student_id values from numeric students.id to students.student_number
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->string('student_id_new')->nullable()->after('student_id');
        });

        DB::statement(
            "UPDATE service_requests sr
             JOIN students s ON sr.student_id = s.id
             SET sr.student_id_new = s.student_number"
        );

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('student_id');
        });

        DB::statement(
            "ALTER TABLE service_requests
             CHANGE student_id_new student_id VARCHAR(255) NOT NULL"
        );

        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreign('student_id')
                ->references('student_number')
                ->on('students')
                ->cascadeOnDelete();
        });

        // 2) Switch students primary key to student_number, keep id as unique key for legacy URLs
        DB::statement(
            "ALTER TABLE students
             DROP PRIMARY KEY,
             ADD UNIQUE KEY students_id_unique (id),
             ADD PRIMARY KEY (student_number)"
        );
    }

    public function down(): void
    {
        if (!Schema::hasColumn('students', 'id')) {
            // If id column no longer exists by design, skip reverse conversion.
            return;
        }

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->unsignedBigInteger('student_id_old')->nullable()->after('student_id');
        });

        DB::statement(
            "UPDATE service_requests sr
             JOIN students s ON sr.student_id = s.student_number
             SET sr.student_id_old = s.id"
        );

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('student_id');
        });

        DB::statement(
            "ALTER TABLE service_requests
             CHANGE student_id_old student_id BIGINT UNSIGNED NOT NULL"
        );

        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();
        });

        DB::statement(
            "ALTER TABLE students
             DROP PRIMARY KEY,
             ADD PRIMARY KEY (id),
             DROP INDEX students_id_unique"
        );
    }
};
