<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_security_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('webhook_request_id')->nullable();
            $table->unsignedBigInteger('imanage_workspace_id')->nullable();
            $table->foreign('imanage_workspace_id')->references('id')->on('imanage_workspaces')->nullOnDelete();
            $table->string('template_workspace_id', 255)->nullable();
            $table->string('target_workspace_id', 255)->nullable();
            $table->json('template_security')->nullable();
            $table->json('target_security')->nullable();
            $table->json('diff')->nullable();
            $table->enum('status', ['match', 'mismatch', 'pending'])->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_security_audits');
    }
};
