<?php

use App\Models\CampaignPrize;
use App\Services\PointsService;
use App\Services\SpinService;
use App\Services\TierService;
use Illuminate\Support\Collection;

/**
 * A wheel with a common slice (weight 5) and a rare slice (weight 1).
 *
 * @return Collection<int, CampaignPrize>
 */
function wheelPrizes(): Collection
{
    return collect([
        new CampaignPrize(['label' => 'common', 'weight' => 5, 'points' => 10]),
        new CampaignPrize(['label' => 'rare', 'weight' => 1, 'points' => 100]),
    ]);
}

it('maps a roll to the right slice of the wheel', function (int $roll, string $expected) {
    $service = new SpinService(new PointsService(new TierService));

    expect($service->pickPrize(wheelPrizes(), $roll)->label)->toBe($expected);
})->with([
    'first slice, lowest roll' => [1, 'common'],
    'first slice, highest roll' => [5, 'common'],
    'second slice' => [6, 'rare'],
]);
