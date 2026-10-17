<?php

namespace Database\Seeders;

use App\Models\Campaign;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Seed demo campaigns, including a spin wheel.
     */
    public function run(): void
    {
        Campaign::updateOrCreate(
            ['slug' => 'double-points-weekend'],
            [
                'name' => 'Double Points Weekend',
                'description' => 'Earn 2x points on every purchase this week.',
                'multiplier_basis_points' => 20_000,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addWeek(),
                'is_active' => true,
            ],
        );

        $wheel = Campaign::updateOrCreate(
            ['slug' => 'welcome-spin'],
            [
                'name' => 'Welcome Spin',
                'description' => 'Spin the wheel once for a welcome bonus.',
                'multiplier_basis_points' => 10_000,
                'starts_at' => null,
                'ends_at' => null,
                'is_active' => true,
            ],
        );

        $prizes = [
            ['label' => '50 points', 'weight' => 40, 'points' => 50, 'stock' => null],
            ['label' => '200 points', 'weight' => 20, 'points' => 200, 'stock' => null],
            ['label' => '500 points', 'weight' => 8, 'points' => 500, 'stock' => null],
            ['label' => 'Better luck next time', 'weight' => 32, 'points' => 0, 'stock' => null],
        ];

        foreach ($prizes as $prize) {
            $wheel->prizes()->updateOrCreate(['label' => $prize['label']], $prize);
        }
    }
}
