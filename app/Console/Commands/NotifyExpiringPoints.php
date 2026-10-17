<?php

namespace App\Console\Commands;

use App\Jobs\SendExpiryReminder;
use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifyExpiringPoints extends Command
{
    protected $signature = 'loyalty:notify-expiring-points';

    protected $description = 'Queue reminders for members whose points expire soon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $from = now();
        $to = now()->addDays((int) config('loyalty.points.reminder_days'));

        $accounts = 0;

        Account::query()
            ->whereHas('transactions', function ($query) use ($from, $to) {
                $query->where('points_remaining', '>', 0)
                    ->whereNull('reminder_sent_at')
                    ->whereBetween('expires_at', [$from, $to]);
            })
            ->chunkById(100, function ($chunk) use ($from, $to, &$accounts) {
                foreach ($chunk as $account) {
                    $batches = $account->transactions()
                        ->where('points_remaining', '>', 0)
                        ->whereNull('reminder_sent_at')
                        ->whereBetween('expires_at', [$from, $to])
                        ->get();

                    if ($batches->isEmpty()) {
                        continue;
                    }

                    /** @var Carbon $expiresAt */
                    $expiresAt = $batches->min('expires_at');

                    SendExpiryReminder::dispatch(
                        $account,
                        (int) $batches->sum('points_remaining'),
                        $expiresAt,
                    );

                    $account->transactions()
                        ->whereIn('id', $batches->modelKeys())
                        ->update(['reminder_sent_at' => now()]);

                    $accounts++;
                }
            });

        $this->info("Queued expiry reminders for {$accounts} accounts.");

        return self::SUCCESS;
    }
}
