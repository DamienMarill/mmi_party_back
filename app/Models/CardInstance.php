<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CardInstance extends Model
{
    use HasUuids;

    protected $fillable = [
        'card_version_id',
        'lootbox_id',
        'user_id',
    ];

    public function rules()
    {
        return [
            'card_version_id' => 'required|exists:card_versions,id',
            'lootbox_id' => 'required|exists:lootboxes,id',
            'user_id' => 'required|exists:users,id',
        ];
    }

    public function cardVersion()
    {
        return $this->belongsTo(CardVersion::class);
    }

    public function lootbox()
    {
        return $this->belongsTo(Lootbox::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
