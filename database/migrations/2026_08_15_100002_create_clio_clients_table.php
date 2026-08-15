<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clio_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('clio_id', 255);
            $table->string('client_id', 255)->nullable();
            $table->string('etag', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('initials', 20)->nullable();
            $table->string('sequence_key', 255)->nullable();
            $table->integer('sequence_number')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'clio_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clio_clients');
    }
};
