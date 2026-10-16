<?php

namespace App\Services;

use App\Models\Tier;
use Illuminate\Support\Collection;

class TierService
{
    /**
     * All tiers ordered from lowest to highest.
     *
     * @return Collection<int, Tier>
     */
    public function all(): Collection
    {
        return Tier::query()->orderBy('min_lifetime_points')->get();
    }

    /**
     * The highest tier whose threshold the member has reached.
     */
    public function tierForLifetimePoints(int $lifetimePoints): ?Tier
    {
        return Tier::query()
            ->where('min_lifetime_points', '<=', $lifetimePoints)
            ->orderByDesc('min_lifetime_points')
            ->first();
    }
}
