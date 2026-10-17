<?php

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\DomainException;
use App\Models\Account;
use App\Models\Campaign;
use App\Models\CampaignPrize;
use App\Models\Spin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SpinService
{
    public function __construct(private readonly PointsService $points) {}

    /**
     * Spin the wheel for an account.
     *
     * The account row is locked first, so a member can only spin a campaign
     * once: a concurrent or repeated request conflicts instead of paying out
     * twice.
     */
    public function spin(Account $account, Campaign $campaign): Spin
    {
        return DB::transaction(function () use ($account, $campaign) {
            $account = Account::query()->lockForUpdate()->findOrFail($account->getKey());
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->getKey());

            if (! $campaign->isRunning()) {
                throw new DomainException('This campaign is not active.');
            }

            $alreadySpun = Spin::query()
                ->where('account_id', $account->getKey())
                ->where('campaign_id', $campaign->getKey())
                ->exists();

            if ($alreadySpun) {
                throw new ConflictException('You have already spun this campaign.');
            }

            $prizes = $campaign->prizes()
                ->lockForUpdate()
                ->get()
                ->filter(fn (CampaignPrize $prize) => $prize->hasStock() && $prize->weight > 0)
                ->values();

            if ($prizes->isEmpty()) {
                throw new DomainException('This campaign has no prizes available.');
            }

            $prize = $this->pickPrize($prizes, random_int(1, (int) $prizes->sum('weight')));

            if ($prize->stock !== null) {
                $prize->decrement('stock');
                $prize->refresh();
            }

            $spin = Spin::create([
                'account_id' => $account->getKey(),
                'campaign_id' => $campaign->getKey(),
                'campaign_prize_id' => $prize->getKey(),
                'points_awarded' => $prize->points,
            ]);

            if ($prize->points > 0) {
                $this->points->bonus(
                    account: $account,
                    points: $prize->points,
                    description: "Spin prize: {$prize->label}",
                    reference: $spin,
                );
            }

            return $spin
                ->setRelation('prize', $prize)
                ->setRelation('campaign', $campaign);
        });
    }

    /**
     * Walk the wheel: subtract each slice's weight until the roll is used up.
     *
     * @param  Collection<int, CampaignPrize>  $prizes
     */
    public function pickPrize(Collection $prizes, int $roll): CampaignPrize
    {
        foreach ($prizes as $prize) {
            $roll -= $prize->weight;

            if ($roll <= 0) {
                return $prize;
            }
        }

        /** @var CampaignPrize $last */
        $last = $prizes->last();

        return $last;
    }
}
