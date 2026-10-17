<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'multiplier_basis_points' => 10_000,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    /**
     * A campaign inside its active window.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
    }

    /**
     * A campaign that has not started yet.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addWeek(),
        ]);
    }

    /**
     * A campaign that has already ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subDay(),
        ]);
    }

    /**
     * A disabled campaign.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the points multiplier as a decimal (1.5 becomes 15_000 basis points).
     */
    public function withMultiplier(float $multiplier): static
    {
        return $this->state(fn (array $attributes) => [
            'multiplier_basis_points' => (int) round($multiplier * 10_000),
        ]);
    }
}
