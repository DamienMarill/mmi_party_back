<?php

use App\Enums\CardTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema builder → DDL adapté au driver (ENUM natif MySQL, CHECK PostgreSQL/SQLite).
        // Les valeurs viennent de l'enum PHP CardTypes : source de vérité unique et portable.
        Schema::table('card_templates', function (Blueprint $table): void {
            $table->enum('type', CardTypes::values())->change();
        });
    }

    public function down(): void
    {
        // État précédent l'ajout de "promo" (valeurs figées : l'enum courant contient promo).
        Schema::table('card_templates', function (Blueprint $table): void {
            $table->enum('type', ['student', 'staff', 'object'])->change();
        });
    }
};
