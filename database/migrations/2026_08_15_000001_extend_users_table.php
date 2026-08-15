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
        Schema::table('users', function (Blueprint $table) {
            // tenant_id added without FK constraint (tenants table does not exist yet)
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');

            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->integer('failed_login_attempts')->default(0)->after('last_login_ip');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');

            $table->softDeletes();

            $table->index('tenant_id', 'users_tenant_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_tenant_id_index');
            $table->dropColumn([
                'tenant_id',
                'last_login_at',
                'last_login_ip',
                'failed_login_attempts',
                'locked_until',
                'deleted_at',
            ]);
        });
    }
};
