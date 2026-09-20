<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Les MMI3 deviennent alumni
        DB::statement("UPDATE users SET groupe = 'alumni' WHERE groupe = 'mmi3'");

        // Les MMI2 deviennent MMI3
        DB::statement("UPDATE users SET groupe = 'mmi3' WHERE groupe = 'mmi2'");

        // Les MMI1 deviennent MMI2
        DB::statement("UPDATE users SET groupe = 'mmi2' WHERE groupe = 'mmi1'");
    }

    public function down(): void
    {
        // Annuler dans l'ordre inverse
        DB::statement("UPDATE users SET groupe = 'mmi1' WHERE groupe = 'mmi2'");
        DB::statement("UPDATE users SET groupe = 'mmi2' WHERE groupe = 'mmi3'");
        DB::statement("UPDATE users SET groupe = 'mmi3' WHERE groupe = 'alumni'");
    }
};