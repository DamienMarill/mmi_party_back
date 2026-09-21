<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolYearTransition extends Model
{
    protected $fillable = [
        'school_year',
        'executed_by',
        'bot_targets',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'bot_targets' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
