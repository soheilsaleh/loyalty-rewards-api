<?php

namespace App\Jobs;

use App\Mail\PointsExpiringReminder;
use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendExpiryReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Account $account,
        public readonly int $points,
        public readonly Carbon $expiresAt,
    ) {}

    /**
     * Send the member a reminder about points that are about to expire.
     */
    public function handle(): void
    {
        $this->account->loadMissing('user');

        $user = $this->account->user;

        if ($user === null) {
            return;
        }

        Mail::to($user->email)->send(
            new PointsExpiringReminder($this->account, $this->points, $this->expiresAt),
        );
    }
}
