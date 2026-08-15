<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('clio_group_id')->nullable();
            $table->foreign('clio_group_id')->references('id')->on('clio_groups')->nullOnDelete();
            $table->unsignedBigInteger('imanage_group_id')->nullable();
            $table->foreign('imanage_group_id')->references('id')->on('imanage_groups')->nullOnDelete();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_mappings');
    }
};
