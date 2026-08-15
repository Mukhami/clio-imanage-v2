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
        Schema::create('legacy_alias_mappings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->enum('entity_type', ['client', 'matter']);
            $table->string('clio_id', 255);
            $table->string('imanage_alias', 255);
            $table->string('imported_from', 255)->nullable();
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'entity_type', 'clio_id'], 'legacy_alias_mappings_tenant_entity_clio_unique');
            $table->index('tenant_id', 'legacy_alias_mappings_tenant_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_alias_mappings');
    }
};
