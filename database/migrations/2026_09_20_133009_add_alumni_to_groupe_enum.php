<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('groupe', ['student', 'staff', 'mmi1', 'mmi2', 'mmi3', 'misc', 'alumni'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('groupe', ['student', 'staff', 'mmi1', 'mmi2', 'mmi3', 'misc'])->change();
        });
    }
};