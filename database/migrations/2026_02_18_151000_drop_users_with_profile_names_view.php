<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS users_with_profile_names');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            'CREATE VIEW users_with_profile_names AS
             SELECT
                u.id,
                u.email,
                u.role,
                u.is_active,
                u.email_verified_at,
                u.student_number,
                s.name AS student_name,
                u.staff_number,
                st.name AS staff_name,
                COALESCE(s.name, st.name) AS profile_name
             FROM users u
             LEFT JOIN students s ON s.student_number = u.student_number
             LEFT JOIN staff st ON st.staff_number = u.staff_number'
        );
    }
};

