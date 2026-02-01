<?php

namespace App\Enums;

enum StockMovementReason: string
{
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Return = 'return';
    case Transfer = 'transfer';
    case Opname = 'opname';

    /**
     * Get the display label for the reason.
     */
    public function label(): string
    {
        return match ($this) {
            self::Adjustment => 'Adjustment',
            self::Sale => 'Sale',
            self::Return => 'Return',
            self::Transfer => 'Transfer',
            self::Opname => 'Stock Opname',
        };
    }

    /**
     * Get all reasons as array for select options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->label(),
        ])->all();
    }
}
