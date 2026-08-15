<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clio_matter_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('clio_id', 255);
            $table->string('name', 255);
            $table->integer('display_order')->default(0);
            $table->unsignedBigInteger('clio_practice_area_id')->nullable();
            $table->foreign('clio_practice_area_id')->references('id')->on('clio_practice_areas')->nullOnDelete();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clio_matter_stages');
    }
};
