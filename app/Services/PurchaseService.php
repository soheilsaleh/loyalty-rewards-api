<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Exceptions\DomainException;
use App\Models\Account;
use App\Models\Purchase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly PointsService $points,
        private readonly CampaignService $campaigns,
    ) {}

    /**
     * Register a purchase and award the points it earns.
     *
     * The points are boosted by the highest multiplier among the campaigns
     * that were running when the purchase happened.
     */
    public function register(
        Account $account,
        int $amountCents,
        ?Carbon $occurredAt = null,
        ?string $externalId = null,
    ): Purchase {
        return DB::transaction(function () use ($account, $amountCents, $occurredAt, $externalId) {
            $at = $occurredAt ?? now();
            $points = $this->points->pointsForAmountCents(
                $amountCents,
                $this->campaigns->highestMultiplierBasisPoints($at),
            );

            $purchase = $account->purchases()->create([
                'external_id' => $externalId,
                'amount_cents' => $amountCents,
                'points_awarded' => $points,
                'status' => PurchaseStatus::Completed,
                'occurred_at' => $at,
            ]);

            if ($points > 0) {
                $this->points->earn(
                    account: $account,
                    points: $points,
                    description: "Purchase #{$purchase->getKey()}",
                    reference: $purchase,
                );
            }

            return $purchase;
        });
    }

    /**
     * Refund a purchase and take back the points it awarded.
     */
    public function refund(Purchase $purchase): Purchase
    {
        if ($purchase->isRefunded()) {
            throw new DomainException('This purchase has already been refunded.');
        }

        return DB::transaction(function () use ($purchase) {
            $purchase->loadMissing('account');

            $this->points->reverse(
                account: $purchase->account,
                points: $purchase->points_awarded,
                description: "Refund for purchase #{$purchase->getKey()}",
                reference: $purchase,
            );

            $purchase->status = PurchaseStatus::Refunded;
            $purchase->refunded_at = now();
            $purchase->save();

            return $purchase;
        });
    }
}
