<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Completed = 'completed';
    case Refunded = 'refunded';

    /**
     * Human-readable label for the purchase status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::Refunded => 'Refunded',
        };
    }
}
