<?php

namespace Database\Seeders;

use App\Models\User;
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
        User::factory()->admin()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@loyalty.test',
        ]);

        User::factory()->staff()->create([
            'name' => 'Demo Staff',
            'email' => 'staff@loyalty.test',
        ]);

        User::factory()->create([
            'name' => 'Demo Customer',
            'email' => 'customer@loyalty.test',
        ]);
    }
}
