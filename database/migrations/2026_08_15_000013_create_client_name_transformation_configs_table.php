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
        Schema::create('client_name_transformation_configs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->unique();
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->enum('strategy', [
                'none',
                'last_name_first',
                'reverse_words',
                'custom_template',
            ])->default('none');

            $table->string('template_pattern', 512)->nullable();
            $table->boolean('apply_to_persons_only')->default(true);
            $table->boolean('enabled')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_name_transformation_configs');
    }
};
