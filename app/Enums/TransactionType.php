<?php

namespace App\Enums;

enum TransactionType: string
{
    case Earn = 'earn';
    case Redeem = 'redeem';
    case Adjust = 'adjust';
    case Expire = 'expire';
    case Refund = 'refund';

    /**
     * Human-readable label for the transaction type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Earn => 'Points earned',
            self::Redeem => 'Reward redeemed',
            self::Adjust => 'Manual adjustment',
            self::Expire => 'Points expired',
            self::Refund => 'Purchase refunded',
        };
    }
}
