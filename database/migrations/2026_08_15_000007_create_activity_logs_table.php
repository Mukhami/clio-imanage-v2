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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->nullOnDelete();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->nullOnDelete();

            $table->string('action', 255);
            $table->string('model_type', 255)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Only created_at, no updated_at
            $table->timestamp('created_at')->nullable();

            $table->index('tenant_id', 'activity_logs_tenant_id_index');
            $table->index('action', 'activity_logs_action_index');
            $table->index('created_at', 'activity_logs_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
