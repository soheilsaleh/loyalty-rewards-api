<?php

namespace App\Enums;

enum TransactionType: string
{
    case Earn = 'earn';
    case Bonus = 'bonus';
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
            self::Bonus => 'Campaign bonus',
            self::Redeem => 'Reward redeemed',
            self::Adjust => 'Manual adjustment',
            self::Expire => 'Points expired',
            self::Refund => 'Purchase refunded',
        };
    }
}
