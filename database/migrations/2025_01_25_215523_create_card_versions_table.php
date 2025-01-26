<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('card_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('card_template_id')->constrained()->cascadeOnDelete();
            $table->enum('rarity', \App\Enums\CardRarity::values());
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_versions');
    }
};
