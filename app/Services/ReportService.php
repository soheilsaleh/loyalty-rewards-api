<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Campaign;
use App\Models\PointTransaction;
use App\Models\Purchase;
use App\Models\Redemption;
use App\Models\Reward;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    public const CACHE_KEY = 'reports:summary';

    /**
     * The cached program summary.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $ttl = (int) config('loyalty.reports.cache_ttl');

        return Cache::remember(self::CACHE_KEY, $ttl, fn () => $this->buildSummary());
    }

    /**
     * Recompute the summary and refresh the cache.
     *
     * @return array<string, mixed>
     */
    public function refresh(): array
    {
        Cache::forget(self::CACHE_KEY);

        return $this->summary();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'members' => Account::query()->count(),
            'points_outstanding' => (int) Account::query()->sum('points_balance'),
            'points_issued' => (int) PointTransaction::query()->where('points', '>', 0)->sum('points'),
            'points_redeemed' => abs((int) PointTransaction::query()->where('type', TransactionType::Redeem)->sum('points')),
            'points_expired' => abs((int) PointTransaction::query()->where('type', TransactionType::Expire)->sum('points')),
            'redemptions' => Redemption::query()->count(),
            'purchases' => Purchase::query()->count(),
            'purchase_amount_cents' => (int) Purchase::query()->sum('amount_cents'),
            'active_rewards' => Reward::query()->where('is_active', true)->count(),
            'active_campaigns' => Campaign::query()->active()->count(),
        ];
    }
}
