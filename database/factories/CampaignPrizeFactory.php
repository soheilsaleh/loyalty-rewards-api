<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignPrize;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CampaignPrize>
 */
class CampaignPrizeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'label' => Str::title(fake()->unique()->words(2, true)),
            'weight' => fake()->numberBetween(1, 10),
            'points' => fake()->numberBetween(0, 500),
            'stock' => null,
        ];
    }
}
