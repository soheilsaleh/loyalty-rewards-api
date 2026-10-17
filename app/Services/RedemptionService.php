<?php

namespace App\Services;

use App\Enums\RedemptionStatus;
use App\Exceptions\ConflictException;
use App\Exceptions\DomainException;
use App\Models\Account;
use App\Models\Redemption;
use App\Models\Reward;
use Illuminate\Support\Facades\DB;

class RedemptionService
{
    public function __construct(private readonly PointsService $points) {}

    /**
     * Redeem a reward for an account.
     *
     * The account row is locked first, so concurrent requests for the same
     * member are serialised and a repeated idempotency key returns the original
     * redemption instead of spending the points twice.
     */
    public function redeem(Account $account, Reward $reward, string $idempotencyKey): Redemption
    {
        return DB::transaction(function () use ($account, $reward, $idempotencyKey) {
            $account = Account::query()->lockForUpdate()->findOrFail($account->getKey());
            $reward = Reward::query()->lockForUpdate()->findOrFail($reward->getKey());

            $existing = Redemption::query()
                ->where('account_id', $account->getKey())
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                if ($existing->reward_id !== $reward->getKey()) {
                    throw new ConflictException('This idempotency key was already used for another reward.');
                }

                return $existing->setRelation('reward', $reward);
            }

            if (! $reward->is_active) {
                throw new DomainException('This reward is not available.');
            }

            if (! $reward->inStock()) {
                throw new DomainException('This reward is out of stock.');
            }

            if ($account->points_balance < $reward->cost_points) {
                throw new DomainException('The account does not have enough points.');
            }

            $redemption = Redemption::create([
                'account_id' => $account->getKey(),
                'reward_id' => $reward->getKey(),
                'points_spent' => $reward->cost_points,
                'status' => RedemptionStatus::Completed,
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->points->redeem(
                account: $account,
                points: $reward->cost_points,
                description: "Reward redeemed: {$reward->name}",
                reference: $redemption,
            );

            if ($reward->stock !== null) {
                $reward->decrement('stock');
                $reward->refresh();
            }

            return $redemption->setRelation('reward', $reward);
        });
    }
}
