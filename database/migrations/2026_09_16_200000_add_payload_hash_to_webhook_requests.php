<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->string('payload_hash', 64)->nullable()->after('body');
            $table->index(['tenant_id', 'payload_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'payload_hash', 'created_at']);
            $table->dropColumn('payload_hash');
        });
    }
};
