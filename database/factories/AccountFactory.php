<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tier_id' => fn () => Tier::query()->orderBy('min_lifetime_points')->value('id'),
            'points_balance' => 0,
            'lifetime_points' => 0,
        ];
    }
}
