<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clio_matters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('clio_id', 255);
            $table->unsignedBigInteger('clio_client_id')->nullable();
            $table->foreign('clio_client_id')->references('id')->on('clio_clients')->nullOnDelete();
            $table->unsignedBigInteger('clio_practice_area_id')->nullable();
            $table->foreign('clio_practice_area_id')->references('id')->on('clio_practice_areas')->nullOnDelete();
            $table->string('matter_id', 255)->nullable();
            $table->string('etag', 255)->nullable();
            $table->string('display_number', 255)->nullable();
            $table->string('custom_number', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 50)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('client_reference', 255)->nullable();
            $table->date('open_date')->nullable();
            $table->date('close_date')->nullable();
            $table->date('pending_date')->nullable();
            $table->json('json_data')->nullable();
            $table->string('sequence_key', 255)->nullable();
            $table->integer('sequence_number')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'clio_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clio_matters');
    }
};
