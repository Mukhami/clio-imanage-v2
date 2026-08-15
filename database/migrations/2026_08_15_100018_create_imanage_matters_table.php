<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imanage_matters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('library_id');
            $table->foreign('library_id')->references('id')->on('libraries')->cascadeOnDelete();
            $table->unsignedBigInteger('imanage_client_id')->nullable();
            $table->foreign('imanage_client_id')->references('id')->on('imanage_clients')->nullOnDelete();
            $table->unsignedBigInteger('webhook_request_id')->nullable();
            $table->unsignedBigInteger('clio_client_id')->nullable();
            $table->foreign('clio_client_id')->references('id')->on('clio_clients')->nullOnDelete();
            $table->unsignedBigInteger('clio_matter_id')->nullable();
            $table->foreign('clio_matter_id')->references('id')->on('clio_matters')->nullOnDelete();
            $table->unsignedBigInteger('clio_practice_area_id')->nullable();
            $table->foreign('clio_practice_area_id')->references('id')->on('clio_practice_areas')->nullOnDelete();
            $table->string('key', 255);
            $table->integer('key_numeric')->nullable();
            $table->string('ssid', 255)->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('hipaa')->default(false);
            $table->boolean('closed')->default(false);
            $table->string('wstype', 50)->nullable();
            $table->unsignedBigInteger('iman_practice_area_id')->nullable();
            $table->foreign('iman_practice_area_id')->references('id')->on('imanage_practice_areas')->nullOnDelete();
            $table->unsignedBigInteger('iman_sub_practice_area_id')->nullable();
            $table->foreign('iman_sub_practice_area_id')->references('id')->on('imanage_sub_practice_areas')->nullOnDelete();
            $table->string('parent_id', 255)->nullable();
            $table->string('parent_ssid', 255)->nullable();
            $table->integer('sequence_number')->nullable();
            $table->string('sequence_key', 255)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'library_id', 'key']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imanage_matters');
    }
};
