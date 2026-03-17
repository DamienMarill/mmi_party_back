<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE card_templates MODIFY COLUMN `type` ENUM('student', 'staff', 'object', 'promo') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE card_templates MODIFY COLUMN `type` ENUM('student', 'staff', 'object') NOT NULL");
    }
};
