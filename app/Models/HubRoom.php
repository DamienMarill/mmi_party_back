<?php

namespace App\Models;

use App\Enums\HubType;
use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HubRoom extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'type',
        'player_one_id',
        'player_two_id',
        'status',
        'invitation_id',
        'metadata',
    ];

    protected $casts = [
        'type' => HubType::class,
        'status' => RoomStatus::class,
        'metadata' => 'array',
    ];

    /**
     * Le premier joueur (initiateur)
     */
    public function playerOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_one_id');
    }

    /**
     * Le second joueur (invité)
     */
    public function playerTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_two_id');
    }

    /**
     * L'invitation qui a créé cette room
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(HubInvitation::class, 'invitation_id');
    }

    /**
     * Vérifie si un utilisateur fait partie de cette room
     */
    public function hasPlayer(User $user): bool
    {
        return $this->player_one_id === $user->id || $this->player_two_id === $user->id;
    }

    /**
     * Vérifie si la room est active
     */
    public function isActive(): bool
    {
        return $this->status === RoomStatus::ACTIVE;
    }
}
