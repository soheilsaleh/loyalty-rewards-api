<?php

namespace App\Enums;

enum RedemptionStatus: string
{
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Human-readable label for the redemption status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
