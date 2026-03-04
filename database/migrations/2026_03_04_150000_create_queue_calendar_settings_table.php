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
        Schema::create('queue_calendar_settings', function (Blueprint $table) {
            $table->id();
            $table->string('timezone')->default('Africa/Kigali');
            $table->unsignedTinyInteger('sabbath_weekday')->default(6);
            $table->json('global_windows')->nullable();
            $table->json('cis_days')->nullable();
            $table->json('cis_windows')->nullable();
            $table->json('holidays')->nullable();
            $table->json('special_rules')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_calendar_settings');
    }
};
