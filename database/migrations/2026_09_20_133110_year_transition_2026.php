<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Les MMI3 deviennent alumni
        DB::table('users')->where('groupe', 'mmi3')->update(['groupe' => 'alumni']);

        // Les MMI2 deviennent MMI3
        DB::table('users')->where('groupe', 'mmi2')->update(['groupe' => 'mmi3']);

        // Les MMI1 deviennent MMI2
        DB::table('users')->where('groupe', 'mmi1')->update(['groupe' => 'mmi2']);
    }

    public function down(): void
    {
        throw new \RuntimeException('Cette migration de transition annuelle n\'est pas réversible sans perte de données.');
    }
};