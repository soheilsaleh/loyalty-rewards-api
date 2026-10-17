<?php

namespace Database\Seeders;

use App\Models\Reward;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    /**
     * Seed the demo reward catalog.
     */
    public function run(): void
    {
        $rewards = [
            [
                'slug' => 'free-coffee',
                'name' => 'Free Coffee',
                'description' => 'A free hot drink of your choice.',
                'cost_points' => 500,
                'stock' => null,
            ],
            [
                'slug' => 'five-euro-voucher',
                'name' => '5 EUR Voucher',
                'description' => 'A 5.00 EUR voucher for your next purchase.',
                'cost_points' => 1_000,
                'stock' => null,
            ],
            [
                'slug' => 'limited-tote-bag',
                'name' => 'Limited Tote Bag',
                'description' => 'A limited edition cotton tote bag.',
                'cost_points' => 2_500,
                'stock' => 50,
            ],
        ];

        foreach ($rewards as $reward) {
            Reward::updateOrCreate(
                ['slug' => $reward['slug']],
                [...$reward, 'is_active' => true],
            );
        }
    }
}
