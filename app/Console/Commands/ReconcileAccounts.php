<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\PointsService;
use Illuminate\Console\Command;

class ReconcileAccounts extends Command
{
    protected $signature = 'loyalty:reconcile {--fix : Correct drifted balances instead of only reporting}';

    protected $description = 'Verify that every cached balance equals the sum of its ledger';

    /**
     * Execute the console command.
     */
    public function handle(PointsService $points): int
    {
        $checked = 0;
        $mismatched = 0;

        Account::query()->chunkById(100, function ($chunk) use ($points, &$checked, &$mismatched) {
            foreach ($chunk as $account) {
                $checked++;

                if ($points->reconcile($account)) {
                    continue;
                }

                $mismatched++;
                $ledger = (int) $account->transactions()->sum('points');

                $this->warn("Account {$account->getKey()}: balance {$account->points_balance} != ledger {$ledger}");

                if ((bool) $this->option('fix')) {
                    $account->points_balance = $ledger;
                    $account->save();
                }
            }
        });

        $this->info("Checked {$checked} accounts; {$mismatched} mismatched.");

        return $mismatched === 0 ? self::SUCCESS : self::FAILURE;
    }
}
