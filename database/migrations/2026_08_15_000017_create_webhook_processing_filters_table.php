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
        Schema::create('webhook_processing_filters', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->string('field_path', 255);
            $table->enum('operator', [
                'equals',
                'not_equals',
                'matches_regex',
                'contains',
                'clio_picklist_equals',
            ]);
            $table->string('value', 512);
            $table->enum('action', ['skip', 'proceed']);
            $table->integer('priority')->default(0);
            $table->boolean('enabled')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'priority'], 'webhook_filters_tenant_priority_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_processing_filters');
    }
};
