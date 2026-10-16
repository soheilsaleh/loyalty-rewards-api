<?php

namespace Database\Factories;

use App\Enums\PurchaseStatus;
use App\Models\Account;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountCents = fake()->numberBetween(100, 500_000);
        $earnRate = (int) config('loyalty.points.earn_rate');

        return [
            'account_id' => Account::factory(),
            'external_id' => (string) Str::uuid(),
            'amount_cents' => $amountCents,
            'points_awarded' => intdiv($amountCents * $earnRate, 100),
            'status' => PurchaseStatus::Completed,
            'occurred_at' => now(),
            'refunded_at' => null,
        ];
    }
}
