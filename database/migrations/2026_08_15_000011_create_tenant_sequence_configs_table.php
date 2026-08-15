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
        Schema::create('tenant_sequence_configs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->unique();
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->string('client_prefix', 20)->nullable();
            $table->integer('client_start_number')->default(1);
            $table->integer('client_current_number')->default(0);
            $table->integer('client_digits')->default(5);
            $table->string('client_custom_field_name', 255)->nullable();

            $table->string('matter_prefix', 20)->nullable();
            $table->integer('matter_start_number')->default(1);
            $table->integer('matter_current_number')->default(0);
            $table->integer('matter_digits')->default(5);
            $table->string('matter_custom_field_name', 255)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_sequence_configs');
    }
};
