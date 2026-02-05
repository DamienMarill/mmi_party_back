<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lootboxes', function (Blueprint $table) {
            // Ajouter le champ slot_used_at pour tracer le timestamp exact du slot utilisé
            $table->timestamp('slot_used_at')->nullable()->after('type');

            // Index composé pour optimiser les requêtes de vérification de disponibilité
            // Utilisé dans: WHERE user_id = X AND slot_used_at = Y AND type = Z
            $table->index(['user_id', 'slot_used_at', 'type'], 'lootboxes_user_slot_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lootboxes', function (Blueprint $table) {
            $table->dropIndex('lootboxes_user_slot_type_index');
            $table->dropColumn('slot_used_at');
        });
    }
};
