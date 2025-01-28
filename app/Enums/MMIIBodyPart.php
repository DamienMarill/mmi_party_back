<?php

namespace App\Enums;

enum MMIIBodyPart: string
{
    case BOUCHE = 'bouche';
    case CHEVEUX = 'cheveux';
    case MAQUILLAGE = 'maquillage';
    case NEZ = 'nez';
    case PARTICULARITES = 'particularites';
    case PILOSITE = 'pilosite';
    case TETE = 'tete';
    case YEUX = 'yeux';

    public function requiresColor(): bool
    {
        return in_array($this, [
            self::BOUCHE,
            self::CHEVEUX,
            self::PILOSITE,
            self::TETE,
            self::YEUX
        ]);
    }

    public function mixBlenMode(): string
    {
        return match ($this) {
            self::BOUCHE => 'multiply',
            self::CHEVEUX => 'multiply',
            self::MAQUILLAGE => 'none',
            self::NEZ => 'none',
            self::PARTICULARITES => 'none',
            self::PILOSITE => 'multiply',
            self::TETE => 'multiply',
            self::YEUX => 'hard-light',
        };
    }
}
