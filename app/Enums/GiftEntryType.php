<?php

namespace App\Enums;

enum GiftEntryType: string
{
    case GIFT = 'gift';
    case CORRECTION = 'correction';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
