<?php

namespace Database\Seeders;

use App\Models\Tier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo accounts.
     *
     * The password for every demo account is "password" and is only meant for
     * local development.
     */
    public function run(): void
    {
        $this->call(TierSeeder::class);
        $this->call(RewardSeeder::class);

        User::factory()->admin()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@loyalty.test',
        ]);

        User::factory()->staff()->create([
            'name' => 'Demo Staff',
            'email' => 'staff@loyalty.test',
        ]);

        $customer = User::factory()->create([
            'name' => 'Demo Customer',
            'email' => 'customer@loyalty.test',
        ]);

        $account = $customer->account()->create([
            'tier_id' => Tier::query()->where('slug', 'bronze')->value('id'),
        ]);

        $purchases = app(PurchaseService::class);

        $purchases->register($account, 12_500, now()->subDays(3), 'demo-purchase-1');
        $purchases->register($account, 4_000, now()->subDay(), 'demo-purchase-2');
    }
}
