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
        Schema::create('custom_field_mapping_rules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->enum('source_type', [
                'matter_status',
                'responsible_attorney',
                'originating_attorney',
                'practice_area',
                'template',
                'clio_custom_field',
                'open_date',
                'static',
            ]);

            $table->string('source_field_name', 255)->nullable();

            // FK to imanage_custom_field_configs table — added in a later migration
            $table->unsignedBigInteger('imanage_custom_field_config_id')->nullable();

            $table->enum('value_mapping_type', ['direct', 'lookup', 'static', 'date_format']);
            $table->string('static_value', 255)->nullable();
            $table->string('date_format', 100)->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('enabled')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'priority'], 'custom_field_rules_tenant_priority_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_field_mapping_rules');
    }
};
