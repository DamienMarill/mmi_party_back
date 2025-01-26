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
        Schema::create('card_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', \App\Enums\CardTypes::values());
            $table->integer('level')->min(1)->max(3)->nullable();
            $table->json('stats')->nullable();
            $table->json('shape')->nullable();
            $table->foreignIdFor(\App\Models\Mmii::class)->nullable();
            $table->foreignUuid('base_user')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_templates');
    }
};
