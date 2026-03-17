<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoUnlock extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'promo_condition_id',
        'unlocked_at',
    ];

    public function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function promoCondition(): BelongsTo
    {
        return $this->belongsTo(PromoCondition::class);
    }
}
