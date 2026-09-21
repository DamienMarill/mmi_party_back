<?php

use App\Enums\CardTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('card_templates', function (Blueprint $table): void {
            $table->boolean('is_lootable')->default(true)->after('base_user');
        });

        DB::statement("
            UPDATE card_templates ct
            INNER JOIN users u ON u.id = ct.base_user
            SET ct.is_lootable = 0
            WHERE ct.type = '".CardTypes::STUDENT->value."'
              AND u.groupe = 'alumni'
        ");
    }

    public function down(): void
    {
        Schema::table('card_templates', function (Blueprint $table): void {
            $table->dropColumn('is_lootable');
        });
    }
};
