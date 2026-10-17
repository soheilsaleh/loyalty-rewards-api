<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Tier;
use App\Models\User;

class AccountService
{
    /**
     * The member's account, created with the lowest tier when missing.
     */
    public function forUser(User $user): Account
    {
        return $user->account()->firstOrCreate([], [
            'tier_id' => Tier::query()->orderBy('min_lifetime_points')->value('id'),
        ]);
    }
}
