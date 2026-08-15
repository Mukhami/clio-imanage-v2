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
        Schema::create('tenant_subscription_reminders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_subscription_id');
            $table->foreign('tenant_subscription_id')
                  ->references('id')->on('tenant_subscriptions')
                  ->cascadeOnDelete();

            $table->enum('reminder_type', ['30_day', '14_day', '7_day', '1_day', 'expired']);
            $table->timestamp('sent_at')->nullable();

            // Only created_at, no updated_at
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_subscription_reminders');
    }
};
