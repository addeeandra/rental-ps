<?php

namespace App\Enums;

enum OrderType: string
{
    case Sales = 'sales';
    case Rental = 'rental';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Sales',
            self::Rental => 'Rental',
        };
    }
}
