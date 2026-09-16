<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->string('failed_at_stage')->nullable()->after('error_count');
            $table->text('skip_reason')->nullable()->after('failed_at_stage');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->dropColumn(['failed_at_stage', 'skip_reason']);
        });
    }
};
