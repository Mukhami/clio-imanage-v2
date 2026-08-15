<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Resolves circular foreign key dependencies now that both users and
     * tenants tables exist.
     */
    public function up(): void
    {
        // users.tenant_id → tenants.id (nullOnDelete)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->nullOnDelete();
        });

        // login_audit_logs.user_id → users.id (nullOnDelete)
        Schema::table('login_audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });

        // tenants.owner_id → users.id (nullOnDelete)
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreign('owner_id')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });

        Schema::table('login_audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
    }
};
