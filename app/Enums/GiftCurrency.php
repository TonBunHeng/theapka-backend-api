<?php

namespace App\Enums;

enum GiftCurrency: string
{
    case KHR = 'KHR';
    case USD = 'USD';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
