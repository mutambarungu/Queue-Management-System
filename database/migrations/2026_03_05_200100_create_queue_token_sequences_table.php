<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_token_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('office_id');
            $table->unsignedBigInteger('sub_office_id')->nullable();
            $table->string('lane_key', 80);
            $table->date('token_date');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['lane_key', 'token_date']);
            $table->index(['office_id', 'sub_office_id', 'token_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_token_sequences');
    }
};
