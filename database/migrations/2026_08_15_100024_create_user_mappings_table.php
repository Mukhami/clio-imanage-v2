<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('clio_user_id')->nullable();
            $table->foreign('clio_user_id')->references('id')->on('clio_users')->nullOnDelete();
            $table->unsignedBigInteger('imanage_user_id')->nullable();
            $table->foreign('imanage_user_id')->references('id')->on('imanage_users')->nullOnDelete();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_mappings');
    }
};
