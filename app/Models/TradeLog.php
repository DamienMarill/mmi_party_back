<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_1_id',
        'user_2_id',
        'card_instance_1_id',
        'card_instance_2_id',
    ];

    /**
     * Le premier joueur impliqué dans l'échange
     */
    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_1_id');
    }

    /**
     * Le second joueur impliqué dans l'échange
     */
    public function user2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_2_id');
    }

    /**
     * La carte donnée par le joueur 1
     */
    public function cardInstance1(): BelongsTo
    {
        return $this->belongsTo(CardInstance::class, 'card_instance_1_id');
    }

    /**
     * La carte donnée par le joueur 2
     */
    public function cardInstance2(): BelongsTo
    {
        return $this->belongsTo(CardInstance::class, 'card_instance_2_id');
    }
}
