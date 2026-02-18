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
        Schema::create('staff', function (Blueprint $table) {
            $table->string('staff_number')->primary();
            $table->string('name');

            // Link to users table
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Nullable foreign keys for office and faculty
            $table->foreignId('office_id')->nullable()->constrained('offices')->onDelete('set null');
            $table->string('position')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
