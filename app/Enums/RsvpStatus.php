<?php

namespace App\Enums;

enum RsvpStatus: string
{
    case ATTENDING = 'attending';
    case DECLINED = 'declined';
    case MAYBE = 'maybe';
    case PENDING = 'pending';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
