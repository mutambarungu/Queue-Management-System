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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();

            $table->string('student_id');
            $table->foreign('student_id')
                ->references('student_number')
                ->on('students')
                ->cascadeOnDelete();

            $table->foreignId('office_id')->constrained('offices');
            $table->foreignId('service_type_id')->constrained('service_types');

            $table->text('description')->nullable();

            $table->enum('status', [
                'Submitted',
                'In Review',
                'Awaiting Student Response',
                'Appointment Required',
                'Appointment Scheduled',
                'Resolved',
                'Closed',
                'Archived'
            ])->default('Submitted');

            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->timestamp('queued_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
