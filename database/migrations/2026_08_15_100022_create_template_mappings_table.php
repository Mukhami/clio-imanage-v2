<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('clio_practice_area_id')->nullable();
            $table->foreign('clio_practice_area_id')->references('id')->on('clio_practice_areas')->nullOnDelete();
            $table->unsignedBigInteger('imanage_template_id')->nullable();
            $table->foreign('imanage_template_id')->references('id')->on('imanage_templates')->nullOnDelete();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_mappings');
    }
};
