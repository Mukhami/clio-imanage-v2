<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imanage_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('imanage_custom_field_config_id');
            $table->foreign('imanage_custom_field_config_id')->references('id')->on('imanage_custom_field_configs')->cascadeOnDelete();
            $table->string('key', 255);
            $table->string('description', 255)->nullable();
            $table->string('wstype', 50)->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imanage_custom_fields');
    }
};
