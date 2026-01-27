<?php

namespace App\Models;

use App\Enums\HubType;
use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HubInvitation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'type',
        'sender_id',
        'receiver_id',
        'status',
        'room_id',
        'expires_at',
    ];

    protected $casts = [
        'type' => HubType::class,
        'status' => InvitationStatus::class,
        'expires_at' => 'datetime',
    ];

    /**
     * L'utilisateur qui a envoyé l'invitation
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * L'utilisateur qui reçoit l'invitation
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * La room créée suite à l'acceptation de l'invitation
     */
    public function room(): HasOne
    {
        return $this->hasOne(HubRoom::class, 'invitation_id');
    }

    /**
     * Vérifie si l'invitation est expirée
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Vérifie si l'invitation est en attente
     */
    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING && !$this->isExpired();
    }
}
