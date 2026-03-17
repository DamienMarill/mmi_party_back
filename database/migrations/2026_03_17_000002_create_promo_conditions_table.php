<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_conditions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('card_version_id')->constrained()->cascadeOnDelete();
            $table->string('condition_type');
            $table->json('condition_data')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_conditions');
    }
};
