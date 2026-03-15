<?php

namespace App\Enums;

enum CardRarity: string
{
    case COMMON = 'common';
    case UNCOMMON = 'uncommon';
    case RARE = 'rare';
    case EPIC = 'epic';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::COMMON => 'Commune',
            self::UNCOMMON => 'Peu commune',
            self::RARE => 'Rare',
            self::EPIC => 'Épique',
        };
    }

    static public function getOrderedValues()
    {
        return [
            self::COMMON->value,
            self::UNCOMMON->value,
            self::RARE->value,
            self::EPIC->value,
        ];
    }
}
