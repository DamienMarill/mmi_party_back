<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PromoCondition extends Model
{
    use HasUuids;

    protected $fillable = [
        'card_version_id',
        'condition_type',
        'condition_data',
        'starts_at',
        'ends_at',
        'active',
    ];

    public function casts(): array
    {
        return [
            'condition_data' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function cardVersion(): BelongsTo
    {
        return $this->belongsTo(CardVersion::class);
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(PromoUnlock::class);
    }

    public function unlockedByUser(string $userId): HasOne
    {
        return $this->hasOne(PromoUnlock::class)->where('user_id', $userId);
    }
}
