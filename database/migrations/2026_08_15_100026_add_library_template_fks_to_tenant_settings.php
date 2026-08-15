<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->foreign('library_id')->references('id')->on('libraries')->nullOnDelete();
            $table->foreign('imanage_template_id')->references('id')->on('imanage_templates')->nullOnDelete();
            $table->foreign('replica_template_id')->references('id')->on('imanage_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropForeign(['imanage_template_id']);
            $table->dropForeign(['replica_template_id']);
        });
    }
};
