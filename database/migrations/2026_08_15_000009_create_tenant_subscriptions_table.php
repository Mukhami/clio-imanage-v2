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
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->string('reference', 255)->unique();
            $table->enum('status', ['active', 'void', 'expired'])->default('active');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('clio_users_at_start')->nullable();
            $table->string('plan_type', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('voided_at')->nullable();

            $table->unsignedBigInteger('voided_by')->nullable();
            $table->foreign('voided_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index('tenant_id', 'tenant_subscriptions_tenant_id_index');
            $table->index('status', 'tenant_subscriptions_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
