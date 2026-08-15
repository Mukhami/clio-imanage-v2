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
        Schema::create('login_audit_logs', function (Blueprint $table) {
            $table->id();
            // user_id without FK constraint yet (added in 000005)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email', 255);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('success');
            $table->timestamp('created_at')->nullable();

            $table->index('user_id', 'login_audit_logs_user_id_index');
            $table->index('email', 'login_audit_logs_email_index');
            $table->index('created_at', 'login_audit_logs_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_audit_logs');
    }
};
