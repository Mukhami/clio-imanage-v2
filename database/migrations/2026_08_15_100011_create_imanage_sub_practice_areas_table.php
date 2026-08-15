<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imanage_sub_practice_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('library_id');
            $table->foreign('library_id')->references('id')->on('libraries')->cascadeOnDelete();
            $table->unsignedBigInteger('imanage_practice_area_id');
            $table->foreign('imanage_practice_area_id')->references('id')->on('imanage_practice_areas')->cascadeOnDelete();
            $table->string('key', 255);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imanage_sub_practice_areas');
    }
};
