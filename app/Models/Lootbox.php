<?php

namespace App\Models;

use App\Enums\LootboxTypes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Lootbox extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'user_id',
        'type',
        'slot_used_at',
    ];

    public function casts()
    {
        return [
            'type' => LootboxTypes::class,
            'slot_used_at' => 'datetime',
        ];
    }

    public function rules()
    {
        return [
            'name' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|enum:' . LootboxTypes::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cards()
    {
        return $this->hasMany(CardInstance::class);
    }
}
