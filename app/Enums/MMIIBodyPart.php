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
            self::CHEVEUX,
            self::MAQUILLAGE,
            self::PILOSITE,
            self::TETE,
            self::YEUX
        ]);
    }
}
