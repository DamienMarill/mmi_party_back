<?php

namespace App\Models;

use App\Enums\CardRarity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardVersion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'rarity',
        'image',
        'card_template_id',
    ];

    public function casts()
    {
        return [
            'rarity' => CardRarity::class,
        ];
    }

    public function rules()
    {
        return [
            'rarity' => 'required|enum:' . CardRarity::class,
            'image' => 'nullable|string',
            'card_template_id' => 'required|exists:card_templates,id',
        ];
    }

    public function cardTemplate()
    {
        return $this->belongsTo(CardTemplate::class);
    }
}
