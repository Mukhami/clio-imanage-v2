<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE webhooks MODIFY COLUMN status ENUM('active', 'pending', 'expired', 'failed') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE webhooks MODIFY COLUMN status ENUM('active', 'expired', 'failed') NOT NULL DEFAULT 'active'");
    }
};
