<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_field_mapping_rules', function (Blueprint $table) {
            $table->foreign('imanage_custom_field_config_id', 'cfmr_imanage_custom_field_config_fk')
                ->references('id')->on('imanage_custom_field_configs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('custom_field_mapping_rules', function (Blueprint $table) {
            $table->dropForeign('cfmr_imanage_custom_field_config_fk');
        });
    }
};
