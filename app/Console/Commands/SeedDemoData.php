<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Tier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedDemoData extends Command
{
    protected $signature = 'loyalty:seed-demo {--members=1000 : Number of members to generate}';

    protected $description = 'Generate a large demo dataset for benchmarking';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $members = max((int) $this->option('members'), 0);

        $this->call('db:seed', ['--class' => 'Database\\Seeders\\TierSeeder', '--force' => true]);

        $tierId = Tier::query()->orderBy('min_lifetime_points')->value('id');
        $password = Hash::make('password');
        $now = now();

        $bar = $this->output->createProgressBar($members);
        $bar->start();

        for ($offset = 0; $offset < $members; $offset += 1000) {
            $size = min(1000, $members - $offset);

            $users = [];
            for ($i = 0; $i < $size; $i++) {
                $number = $offset + $i + 1;
                $users[] = [
                    'name' => "Demo Member {$number}",
                    'email' => "demo{$number}@loyalty.test",
                    'password' => $password,
                    'role' => Role::Customer->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('users')->insert($users);
            $firstUserId = (int) DB::getPdo()->lastInsertId();

            $accounts = [];
            for ($i = 0; $i < $size; $i++) {
                $accounts[] = [
                    'user_id' => $firstUserId + $i,
                    'tier_id' => $tierId,
                    'points_balance' => 0,
                    'lifetime_points' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('accounts')->insert($accounts);
            $firstAccountId = (int) DB::getPdo()->lastInsertId();

            $entries = [];
            $balances = [];
            for ($i = 0; $i < $size; $i++) {
                $accountId = $firstAccountId + $i;
                $balance = 0;

                foreach ([100, 250, 400] as $points) {
                    $balance += $points;
                    $entries[] = [
                        'account_id' => $accountId,
                        'type' => 'earn',
                        'points' => $points,
                        'balance_after' => $balance,
                        'description' => 'Demo purchase',
                        'points_remaining' => $points,
                        'expires_at' => $now->copy()->addMonths(12),
                        'created_at' => $now,
                    ];
                }

                $balances[$accountId] = $balance;
            }

            DB::table('point_transactions')->insert($entries);

            foreach ($balances as $accountId => $balance) {
                DB::table('accounts')->where('id', $accountId)->update([
                    'points_balance' => $balance,
                    'lifetime_points' => $balance,
                ]);
            }

            $bar->advance($size);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Generated {$members} demo members.");

        return self::SUCCESS;
    }
}
