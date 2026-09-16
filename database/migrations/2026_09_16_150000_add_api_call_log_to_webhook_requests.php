<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->json('api_call_log')->nullable()->after('skip_reason');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->dropColumn('api_call_log');
        });
    }
};
