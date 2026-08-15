<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->notNull();
            $table->string('model', 255)->notNull();
            $table->string('event', 255)->notNull();
            $table->timestamps();

            $table->unique(['model', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_types');
    }
};
