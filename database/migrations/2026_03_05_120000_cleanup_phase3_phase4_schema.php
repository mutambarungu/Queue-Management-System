<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_requests')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $foreignColumns = [
                    'merged_into_request_id',
                    'reassigned_from_office_id',
                    'reassigned_by_user_id',
                    'cancelled_by_user_id',
                ];

                foreach ($foreignColumns as $column) {
                    if (Schema::hasColumn('service_requests', $column)) {
                        try {
                            $table->dropForeign([$column]);
                        } catch (Throwable $e) {
                            // Ignore if FK does not exist; continue cleanup.
                        }
                        $table->dropColumn($column);
                    }
                }

                $columns = [
                    'reassigned_at',
                    'reassigned_reason',
                    'cancellation_reason',
                    'cancelled_at',
                    'auto_closed_at',
                    'last_student_reply_at',
                    'last_staff_reply_at',
                    'next_notified_at',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('service_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('service_request_replies') && Schema::hasColumn('service_request_replies', 'is_internal')) {
            Schema::table('service_request_replies', function (Blueprint $table) {
                $table->dropColumn('is_internal');
            });
        }

        Schema::dropIfExists('service_request_audits');
        Schema::dropIfExists('request_templates');
    }

    public function down(): void
    {
        // Intentionally left blank: this cleanup migration is not reversible.
    }
};
