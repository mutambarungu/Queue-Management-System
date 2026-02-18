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
        $legacyUsersStaffId = Schema::hasColumn('users', 'staff_id');
        $legacyAppointmentsStaffId = Schema::hasColumn('appointments', 'staff_id');
        $hasStaffNumberForeign = function (string $tableName): bool {
            return (bool) DB::selectOne(
                "SELECT 1
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = 'staff_number'
                   AND REFERENCED_TABLE_NAME = 'staff'
                 LIMIT 1",
                [$tableName]
            );
        };

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'staff_number')) {
                $table->string('staff_number')->nullable()->after('student_number');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'staff_number')) {
                $table->string('staff_number')->nullable()->after('status');
            }
        });

        // Backfill from legacy numeric staff_id.
        if ($legacyUsersStaffId) {
            DB::statement(
                'UPDATE users u
                 JOIN staff st ON st.id = u.staff_id
                 SET u.staff_number = st.staff_number
                 WHERE u.staff_id IS NOT NULL'
            );
        }

        if ($legacyAppointmentsStaffId) {
            DB::statement(
                'UPDATE appointments a
                 JOIN staff st ON st.id = a.staff_id
                 SET a.staff_number = st.staff_number
                 WHERE a.staff_id IS NOT NULL'
            );
        }

        // Ensure staff_number links are complete even if staff_id was absent.
        DB::statement(
            'UPDATE users u
             JOIN staff st ON st.user_id = u.id
             SET u.staff_number = st.staff_number
             WHERE u.role = "staff" AND (u.staff_number IS NULL OR u.staff_number = "")'
        );

        if ($legacyUsersStaffId) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['staff_id']);
            });
        }

        if ($legacyAppointmentsStaffId) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropForeign(['staff_id']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
        });

        // Temporarily remove staff_number foreign keys to allow index/primary key changes.
        if ($hasStaffNumberForeign('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['staff_number']);
            });
        }

        if ($hasStaffNumberForeign('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropForeign(['staff_number']);
            });
        }

        // Drop legacy auto-increment id first; then ensure staff_number is primary key.
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'id')) {
                $table->dropColumn('id');
            }
        });

        $primaryOnStaffNumber = DB::selectOne(
            "SELECT 1
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'staff'
               AND CONSTRAINT_NAME = 'PRIMARY'
               AND COLUMN_NAME = 'staff_number'
             LIMIT 1"
        );

        if (!$primaryOnStaffNumber) {
            $legacyUniqueOnStaffNumber = DB::selectOne(
                "SELECT 1
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'staff'
                   AND INDEX_NAME = 'staff_staff_number_unique'
                 LIMIT 1"
            );

            if ($legacyUniqueOnStaffNumber) {
                DB::statement('ALTER TABLE staff DROP INDEX staff_staff_number_unique');
            }

            DB::statement('ALTER TABLE staff ADD PRIMARY KEY (staff_number)');
        }

        // Re-add staff_number foreign keys.
        if (!$hasStaffNumberForeign('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('staff_number')
                    ->references('staff_number')
                    ->on('staff')
                    ->nullOnDelete();
            });
        }

        if (!$hasStaffNumberForeign('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreign('staff_number')
                    ->references('staff_number')
                    ->on('staff')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'id')) {
                $table->unsignedBigInteger('id')->autoIncrement()->first();
            }
        });

        DB::statement('ALTER TABLE staff DROP PRIMARY KEY, ADD PRIMARY KEY (id)');

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'staff_id')) {
                $table->unsignedBigInteger('staff_id')->nullable()->after('student_number');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'staff_id')) {
                $table->unsignedBigInteger('staff_id')->nullable()->after('status');
            }
        });

        DB::statement(
            'UPDATE users u
             JOIN staff st ON st.staff_number = u.staff_number
             SET u.staff_id = st.id
             WHERE u.staff_number IS NOT NULL'
        );

        DB::statement(
            'UPDATE appointments a
             JOIN staff st ON st.staff_number = a.staff_number
             SET a.staff_id = st.id
             WHERE a.staff_number IS NOT NULL'
        );

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['staff_number']);
            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->nullOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['staff_number']);
            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'staff_number')) {
                $table->dropColumn('staff_number');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'staff_number')) {
                $table->dropColumn('staff_number');
            }
        });
    }
};
