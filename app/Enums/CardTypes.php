<?php

namespace App\Enums;

enum CardTypes: string
{
    case STUDENT = 'student';
    case STAFF = 'staff';
    case OBJECT = 'object';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Étudiant',
            self::STAFF => 'Personnel',
            self::OBJECT => 'Objet',
        };
    }
}
