<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Campaign;
use App\Models\Spin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Spin>
 */
class SpinFactory extends Factory
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
            'campaign_id' => Campaign::factory(),
            'campaign_prize_id' => null,
            'points_awarded' => 0,
        ];
    }
}
