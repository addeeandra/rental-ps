<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Partial => 'Partial',
            self::Paid => 'Paid',
            self::Void => 'Void',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'warning',
            self::Partial => 'info',
            self::Paid => 'success',
            self::Void => 'secondary',
        };
    }
}
