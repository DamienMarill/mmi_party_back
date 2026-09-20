<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN groupe ENUM('student', 'staff', 'mmi1', 'mmi2', 'mmi3', 'misc', 'alumni')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN groupe ENUM('student', 'staff', 'mmi1', 'mmi2', 'mmi3', 'misc')");
    }
};