<?php

namespace App\Enums;

enum CardTypes: string
{
    case STUDENT = 'student';
    case STAFF = 'staff';
    case OBJECT = 'object';
    case PROMO = 'promo';

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
            self::PROMO => 'Promo',
        };
    }

    public static function getOrderedValues()
    {
        return [
            self::STUDENT->value,
            self::STAFF->value,
            self::OBJECT->value,
            self::PROMO->value,
        ];
    }
}
