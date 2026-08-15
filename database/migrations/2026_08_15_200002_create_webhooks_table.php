<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->bigInteger('clio_id')->notNull();
            $table->unsignedBigInteger('webhook_type_id');
            $table->foreign('webhook_type_id')->references('id')->on('webhook_types')->restrictOnDelete();
            $table->string('url', 512)->notNull();
            $table->text('shared_secret')->nullable();
            $table->enum('status', ['active', 'expired', 'failed'])->notNull()->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->string('etag', 255)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'clio_id']);
            $table->index('tenant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
