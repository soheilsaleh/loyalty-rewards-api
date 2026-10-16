<?php

namespace Database\Seeders;

use App\Models\Tier;
use Illuminate\Database\Seeder;

class TierSeeder extends Seeder
{
    /**
     * Seed the tiers defined in config/loyalty.php.
     */
    public function run(): void
    {
        /** @var array<int, array{slug: string, name: string, min_lifetime_points: int}> $tiers */
        $tiers = config('loyalty.tiers');

        foreach ($tiers as $tier) {
            Tier::updateOrCreate(
                ['slug' => $tier['slug']],
                [
                    'name' => $tier['name'],
                    'min_lifetime_points' => $tier['min_lifetime_points'],
                ],
            );
        }
    }
}
