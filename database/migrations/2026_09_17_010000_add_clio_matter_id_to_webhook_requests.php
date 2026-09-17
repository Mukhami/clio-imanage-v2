<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->string('clio_matter_id')->nullable()->after('correlation_id');
            $table->index(['tenant_id', 'clio_matter_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'clio_matter_id', 'created_at']);
            $table->dropColumn('clio_matter_id');
        });
    }
};
