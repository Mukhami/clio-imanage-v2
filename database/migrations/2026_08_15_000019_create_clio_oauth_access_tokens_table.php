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
        Schema::create('clio_oauth_access_tokens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('access_expires_at');
            $table->timestamp('refresh_expires_at')->nullable();
            $table->boolean('revoked')->default(false);

            $table->timestamps();

            $table->index(['tenant_id', 'revoked'], 'clio_oauth_tokens_tenant_revoked_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clio_oauth_access_tokens');
    }
};
