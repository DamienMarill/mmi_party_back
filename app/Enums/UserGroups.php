<?php

namespace App\Enums;

enum UserGroups: string
{
    case STUDENT = 'student';
    case STAFF = 'staff';
    case MMI1 = 'mmi1';
    case MMI2 = 'mmi2';
    case MMI3 = 'mmi3';
    case MISC = 'misc';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Étudiant',
            self::STAFF => 'Personnel',
            self::MMI1 => 'MMI 1',
            self::MMI2 => 'MMI 2',
            self::MMI3 => 'MMI 3',
            self::MISC => 'Divers',
        };
    }
}
