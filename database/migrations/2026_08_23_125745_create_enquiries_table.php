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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('firm_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->enum('firm_size', ['solo', 'small', 'mid', 'large', 'enterprise']);
            $table->enum('clio_region', ['us', 'ca', 'eu', 'au', 'uk']);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'contacted', 'onboarded', 'declined'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
