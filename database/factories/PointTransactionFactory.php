<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\PointTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointTransaction>
 */
class PointTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $points = fake()->numberBetween(1, 500);

        return [
            'account_id' => Account::factory(),
            'type' => TransactionType::Earn,
            'points' => $points,
            'balance_after' => $points,
            'description' => null,
            'points_remaining' => $points,
            'expires_at' => null,
        ];
    }
}
