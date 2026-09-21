<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_year_transitions', function (Blueprint $table): void {
            $table->id();
            $table->string('school_year')->unique();
            $table->foreignUuid('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('bot_targets');
            $table->timestamp('executed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_year_transitions');
    }
};
