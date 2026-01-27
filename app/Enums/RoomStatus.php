<?php

namespace App\Enums;

enum RoomStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case ABANDONED = 'abandoned';
}
