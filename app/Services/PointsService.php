<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\DomainException;
use App\Models\Account;
use App\Models\PointTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PointsService
{
    public function __construct(private readonly TierService $tiers) {}

    /**
     * Points earned for a purchase amount, floored to a whole point.
     */
    public function pointsForAmountCents(int $amountCents): int
    {
        $rate = (int) config('loyalty.points.earn_rate');

        return intdiv(max($amountCents, 0) * $rate, 100);
    }

    /**
     * When a batch of points earned now would expire.
     */
    public function expiryDate(?Carbon $from = null): Carbon
    {
        $months = (int) config('loyalty.points.expiry_months');

        return ($from ?? now())->copy()->addMonths($months);
    }

    /**
     * Award a positive amount of points (purchase, bonus, ...).
     */
    public function earn(
        Account $account,
        int $points,
        string $description,
        ?Model $reference = null,
        ?Carbon $expiresAt = null,
    ): PointTransaction {
        if ($points <= 0) {
            throw new InvalidArgumentException('Points to earn must be greater than zero.');
        }

        return DB::transaction(function () use ($account, $points, $description, $reference, $expiresAt) {
            $account = $this->lockedAccount($account);

            return $this->writeEntry(
                account: $account,
                type: TransactionType::Earn,
                points: $points,
                description: $description,
                reference: $reference,
                expiresAt: $expiresAt ?? $this->expiryDate(),
                affectsLifetime: true,
                holdsRemaining: true,
            );
        });
    }

    /**
     * Apply a manual adjustment. Positive amounts earn points, negative
     * amounts remove them, and the balance may never go below zero.
     */
    public function adjust(Account $account, int $points, string $description): PointTransaction
    {
        if ($points === 0) {
            throw new InvalidArgumentException('Adjustment must not be zero.');
        }

        return DB::transaction(function () use ($account, $points, $description) {
            $account = $this->lockedAccount($account);

            return $this->writeEntry(
                account: $account,
                type: TransactionType::Adjust,
                points: $points,
                description: $description,
                reference: null,
                expiresAt: $points > 0 ? $this->expiryDate() : null,
                affectsLifetime: $points > 0,
                holdsRemaining: true,
            );
        });
    }

    /**
     * Reverse previously earned points, for example when a purchase is
     * refunded. The deduction is capped at the available balance and `null`
     * is returned when there is nothing left to take back.
     */
    public function reverse(
        Account $account,
        int $points,
        string $description,
        ?Model $reference = null,
    ): ?PointTransaction {
        return DB::transaction(function () use ($account, $points, $description, $reference) {
            $account = $this->lockedAccount($account);
            $deduction = min(max($points, 0), $account->points_balance);

            if ($deduction === 0) {
                return null;
            }

            return $this->writeEntry(
                account: $account,
                type: TransactionType::Refund,
                points: -$deduction,
                description: $description,
                reference: $reference,
                expiresAt: null,
                affectsLifetime: true,
                holdsRemaining: false,
            );
        });
    }

    /**
     * Whether the cached balance still equals the sum of the ledger.
     */
    public function reconcile(Account $account): bool
    {
        $ledgerTotal = (int) $account->transactions()->sum('points');

        return $ledgerTotal === $account->points_balance;
    }

    private function lockedAccount(Account $account): Account
    {
        return Account::query()->lockForUpdate()->findOrFail($account->getKey());
    }

    private function writeEntry(
        Account $account,
        TransactionType $type,
        int $points,
        ?string $description,
        ?Model $reference,
        ?Carbon $expiresAt,
        bool $affectsLifetime,
        bool $holdsRemaining,
    ): PointTransaction {
        $balanceAfter = $account->points_balance + $points;

        if ($balanceAfter < 0) {
            throw new DomainException('The account does not have enough points.');
        }

        $entry = new PointTransaction([
            'type' => $type,
            'points' => $points,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'points_remaining' => $holdsRemaining && $points > 0 ? $points : 0,
            'expires_at' => $expiresAt,
        ]);

        $entry->account()->associate($account);

        if ($reference !== null) {
            $entry->reference()->associate($reference);
        }

        $entry->save();

        $account->points_balance = $balanceAfter;

        if ($affectsLifetime) {
            $account->lifetime_points = max(0, $account->lifetime_points + $points);
        }

        $tier = $this->tiers->tierForLifetimePoints($account->lifetime_points);

        if ($tier !== null) {
            $account->tier_id = $tier->getKey();
        }

        $account->save();

        return $entry;
    }
}
