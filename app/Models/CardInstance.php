<?php

namespace App\Models;

use App\Enums\CardRarity;
use App\Enums\CardTypes;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeOrderCard($query)
    {
        return $query->with(['cardVersion', 'cardVersion.cardTemplate'])
            ->orderBy('cardVersion.rarity')
            ->orderBy('cardTemplate.type')
            ->orderBy('cardTemplate.level')
            ->orderBy('cardTemplate.name');
    }

    public function scopeOrderByCardAttributes(Builder $query): Builder
    {
        $rarityOrder = implode(',', array_map(
            fn($value) => "'" . $value . "'",
            CardRarity::getOrderedValues()
        ));

        $typeOrder = implode(',', array_map(
            fn($value) => "'" . $value . "'",
            CardTypes::getOrderedValues()
        ));

        return $query->with(['cardVersion.cardTemplate'])
            ->orderByRaw("FIELD((". CardVersion::select('rarity')
                    ->whereColumn('card_versions.id', 'card_instances.card_version_id')
                    ->toSql() ."), {$rarityOrder})")
            ->orderByRaw("FIELD((". CardTemplate::select('type')
                    ->join('card_versions', 'card_templates.id', '=', 'card_versions.card_template_id')
                    ->whereColumn('card_versions.id', 'card_instances.card_version_id')
                    ->toSql() ."), {$typeOrder})")
            ->orderBy(CardTemplate::select('level')
                ->join('card_versions', 'card_templates.id', '=', 'card_versions.card_template_id')
                ->whereColumn('card_versions.id', 'card_instances.card_version_id'))
            ->orderBy(CardTemplate::select('name')
                ->join('card_versions', 'card_templates.id', '=', 'card_versions.card_template_id')
                ->whereColumn('card_versions.id', 'card_instances.card_version_id'));
    }
}
