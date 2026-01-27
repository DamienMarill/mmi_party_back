<?php

use App\Enums\HubType;
use App\Enums\RoomStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hub_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', array_column(HubType::cases(), 'value'));
            $table->foreignUuid('player_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('player_two_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', array_column(RoomStatus::cases(), 'value'))->default(RoomStatus::ACTIVE->value);
            $table->foreignUuid('invitation_id')->constrained('hub_invitations')->cascadeOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Ajouter la clé étrangère pour room_id dans hub_invitations (maintenant que hub_rooms existe)
        Schema::table('hub_invitations', function (Blueprint $table) {
            $table->foreign('room_id')->references('id')->on('hub_rooms')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hub_invitations', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
        });
        Schema::dropIfExists('hub_rooms');
    }
};
