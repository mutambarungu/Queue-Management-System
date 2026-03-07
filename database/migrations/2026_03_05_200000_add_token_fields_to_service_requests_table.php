<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('token_prefix', 20)->nullable()->after('request_number');
            $table->unsignedInteger('token_number')->nullable()->after('token_prefix');
            $table->date('token_date')->nullable()->after('token_number');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['token_prefix', 'token_number', 'token_date']);
        });
    }
};
