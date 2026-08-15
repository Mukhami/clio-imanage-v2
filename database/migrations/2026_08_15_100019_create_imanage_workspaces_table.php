<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imanage_workspaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('library_id');
            $table->foreign('library_id')->references('id')->on('libraries')->cascadeOnDelete();
            $table->unsignedBigInteger('imanage_template_id')->nullable();
            $table->foreign('imanage_template_id')->references('id')->on('imanage_templates')->nullOnDelete();
            $table->unsignedBigInteger('imanage_matter_id')->nullable();
            $table->foreign('imanage_matter_id')->references('id')->on('imanage_matters')->nullOnDelete();
            $table->unsignedBigInteger('imanage_client_id')->nullable();
            $table->foreign('imanage_client_id')->references('id')->on('imanage_clients')->nullOnDelete();
            $table->unsignedBigInteger('iman_practice_area_id')->nullable();
            $table->foreign('iman_practice_area_id')->references('id')->on('imanage_practice_areas')->nullOnDelete();
            $table->unsignedBigInteger('iman_sub_practice_area_id')->nullable();
            $table->foreign('iman_sub_practice_area_id')->references('id')->on('imanage_sub_practice_areas')->nullOnDelete();
            $table->unsignedBigInteger('webhook_request_id')->nullable();
            $table->string('imanage_workspace_id', 255)->nullable();
            $table->string('name', 500)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('database', 255)->nullable();
            $table->string('default_security', 50)->nullable();
            $table->boolean('has_subfolders')->default(false);
            $table->string('owner', 255)->nullable();
            $table->string('document_number', 255)->nullable();
            $table->boolean('is_declared')->default(false);
            $table->boolean('is_hipaa')->default(false);
            $table->string('iwl', 512)->nullable();
            $table->boolean('replica')->default(false);

            foreach (range(1, 30) as $i) {
                $table->string("custom{$i}", 255)->nullable();
            }

            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'imanage_workspace_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imanage_workspaces');
    }
};
