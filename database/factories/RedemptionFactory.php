<?php

namespace Database\Factories;

use App\Enums\RedemptionStatus;
use App\Models\Account;
use App\Models\Redemption;
use App\Models\Reward;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Redemption>
 */
class RedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'reward_id' => Reward::factory(),
            'points_spent' => fake()->numberBetween(100, 2_000),
            'status' => RedemptionStatus::Completed,
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
