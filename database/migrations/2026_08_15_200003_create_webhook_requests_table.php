<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('webhook_id')->nullable();
            $table->foreign('webhook_id')->references('id')->on('webhooks')->nullOnDelete();
            $table->string('url', 512)->notNull();
            $table->json('headers')->nullable();
            $table->json('body')->nullable();
            $table->char('correlation_id', 36)->notNull()->unique();
            $table->enum('processing_stage', [
                'received',
                'validated',
                'parsed',
                'filtered',
                'enqueued',
                'processing',
                'post_processing',
                'completed',
                'failed',
                'skipped',
            ])->notNull()->default('received');
            $table->string('retrieved_client_id', 32)->nullable();
            $table->string('retrieved_matter_id', 32)->nullable();
            $table->boolean('client_activity_complete')->notNull()->default(false);
            $table->boolean('matter_activity_complete')->notNull()->default(false);
            $table->boolean('workspace_activity_complete')->notNull()->default(false);
            $table->boolean('folder_activity_complete')->notNull()->default(false);
            $table->boolean('security_activity_complete')->notNull()->default(false);
            $table->boolean('workspace_link_custom_field_populated')->notNull()->default(false);
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('error_count')->notNull()->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('reattempted')->notNull()->default(false);
            $table->unsignedBigInteger('reattempted_by')->nullable();
            $table->timestamp('reattempted_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'webhook_requests_tenant_id_index');
            $table->index('processing_stage', 'webhook_requests_processing_stage_index');
            $table->index('created_at', 'webhook_requests_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_requests');
    }
};
