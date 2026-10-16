<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Customer = 'customer';

    /**
     * Human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Staff => 'Staff member',
            self::Customer => 'Customer',
        };
    }
}
