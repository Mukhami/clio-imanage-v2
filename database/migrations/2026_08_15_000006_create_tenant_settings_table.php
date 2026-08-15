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
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->unique();
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            // library_id FK added after imanage_libraries table exists
            $table->unsignedBigInteger('library_id')->nullable();

            // imanage_template_id FK added after imanage_workspaces/templates table exists
            $table->unsignedBigInteger('imanage_template_id')->nullable();

            $table->boolean('default_hipaa')->default(false);
            $table->boolean('default_enabled')->default(true);
            $table->boolean('has_replica_workspaces')->default(false);
            $table->unsignedBigInteger('replica_template_id')->nullable();
            $table->boolean('has_workspace_link_custom_field')->default(false);
            $table->string('workspace_link_custom_field_name', 255)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
