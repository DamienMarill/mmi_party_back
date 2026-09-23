<?php

use App\Enums\UserGroups;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // On passe par le schema builder : Laravel génère le DDL adapté au driver
        // (ENUM natif sur MySQL, contrainte CHECK sur PostgreSQL/SQLite). Les valeurs
        // sont dérivées de l'enum PHP UserGroups → source de vérité unique et portable.
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('groupe', UserGroups::values())->nullable()->change();
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('Cette migration n\'est pas réversible sans risque de corruption des données alumni.');
    }
};
