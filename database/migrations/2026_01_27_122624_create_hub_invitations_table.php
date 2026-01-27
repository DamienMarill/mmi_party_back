<?php

use App\Enums\HubType;
use App\Enums\InvitationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hub_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', array_column(HubType::cases(), 'value'));
            $table->foreignUuid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', array_column(InvitationStatus::cases(), 'value'))->default(InvitationStatus::PENDING->value);
            $table->uuid('room_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index(['sender_id', 'status']);
            $table->index(['receiver_id', 'status']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hub_invitations');
    }
};
