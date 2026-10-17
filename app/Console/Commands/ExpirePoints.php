<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\PointsService;
use Illuminate\Console\Command;

class ExpirePoints extends Command
{
    protected $signature = 'loyalty:expire-points';

    protected $description = 'Expire points whose batches have passed their expiry date';

    /**
     * Execute the console command.
     */
    public function handle(PointsService $points): int
    {
        $accounts = 0;
        $expired = 0;

        Account::query()
            ->whereHas('transactions', function ($query) {
                $query->where('points_remaining', '>', 0)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
            })
            ->chunkById(100, function ($chunk) use ($points, &$accounts, &$expired) {
                foreach ($chunk as $account) {
                    $entry = $points->expireDue($account);

                    if ($entry !== null) {
                        $accounts++;
                        $expired += abs($entry->points);
                    }
                }
            });

        $this->info("Expired {$expired} points across {$accounts} accounts.");

        return self::SUCCESS;
    }
}
