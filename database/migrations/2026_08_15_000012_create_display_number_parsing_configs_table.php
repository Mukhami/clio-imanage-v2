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
        Schema::create('display_number_parsing_configs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->unique();
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->enum('strategy', [
                'split_delimiter',
                'split_delimiter_nested',
                'regex',
                'bracket_extraction',
                'clio_ids',
                'custom_field_extraction',
                'display_number_as_matter',
                'display_number_as_client',
                'sequence_auto',
                'legacy_alias_lookup',
                'custom',
            ]);

            $table->string('delimiter', 10)->nullable();
            $table->string('secondary_delimiter', 10)->nullable();
            $table->integer('client_position')->nullable();
            $table->integer('matter_position')->nullable();
            $table->string('regex_pattern', 512)->nullable();
            $table->string('client_capture_group', 50)->nullable();
            $table->string('matter_capture_group', 50)->nullable();
            $table->json('pre_processing_rules')->nullable();
            $table->json('post_processing_rules')->nullable();
            $table->string('validation_regex', 512)->nullable();
            $table->string('matter_status_filter', 50)->nullable();
            $table->string('custom_field_name', 255)->nullable();
            $table->string('fallback_strategy', 50)->nullable();
            $table->json('fallback_config')->nullable();
            $table->boolean('enabled')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('display_number_parsing_configs');
    }
};
