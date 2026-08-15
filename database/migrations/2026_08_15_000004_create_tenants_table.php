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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->uuid('reference')->unique();
            $table->enum('status', ['pending', 'active', 'suspended', 'archived'])->default('pending');

            $table->unsignedBigInteger('clio_location_id')->nullable();
            $table->foreign('clio_location_id')
                  ->references('id')->on('clio_locations')
                  ->cascadeOnDelete();

            $table->text('clio_app_id')->nullable();
            $table->text('clio_app_secret')->nullable();

            $table->string('imanage_cloud_url', 512)->nullable();
            $table->string('imanage_customer_id', 255)->nullable();
            $table->text('imanage_app_id')->nullable();
            $table->text('imanage_app_secret')->nullable();
            $table->text('imanage_username')->nullable();
            $table->text('imanage_password')->nullable();

            $table->boolean('password_authentication')->default(false);
            $table->boolean('has_group_security_mapping')->default(false);
            $table->boolean('enable_workspace_link_custom_field')->default(false);

            // owner_id FK to users added in 000005 (circular dependency)
            $table->unsignedBigInteger('owner_id')->nullable();

            $table->timestamp('onboarded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'tenants_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
