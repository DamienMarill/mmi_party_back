<?php

namespace App\Enums;

enum CardRarity: string
{
    case COMMON = 'common';
    case UNCOMMON = 'uncommon';
    case RARE = 'rare';
    case EPIC = 'epic';
    case LEGENDARY = 'legendary';

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
            self::LEGENDARY => 'Légendaire',
        };
    }

    public function dropRate(): float
    {
        return match ($this) {
            self::COMMON => 0.70,
            self::UNCOMMON => 0.20,
            self::RARE => 0.08,
            self::EPIC => 0.02,
            self::LEGENDARY => 0,
        };
    }
}
