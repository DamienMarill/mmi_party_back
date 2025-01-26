<?php

namespace App\Enums;

enum LootboxTypes: string
{
    case QUOTIDIAN = 'quotidian';
    case STARTER = 'starter';
    case PURCHASED = 'purchased';
    case GIFTED = 'gifted';
    case MISC = 'misc';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::QUOTIDIAN => 'Quotidienne',
            self::STARTER => 'Débutante',
            self::PURCHASED => 'Achetée',
            self::GIFTED => 'Offerte',
            self::MISC => 'Divers',
        };
    }
}
