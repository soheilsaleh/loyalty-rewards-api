<?php

use App\Services\PointsService;
use App\Services\TierService;

it('converts an amount in cents to whole points', function (int $cents, int $expected) {
    $service = new PointsService(new TierService);

    expect($service->pointsForAmountCents($cents))->toBe($expected);
})->with([
    'free purchase' => [0, 0],
    'one currency unit' => [100, 10],
    'two and a half' => [250, 25],
    'floors the remainder' => [255, 25],
    'just under ten' => [999, 99],
]);

it('applies a campaign multiplier to the earned points', function (int $cents, int $basisPoints, int $expected) {
    $service = new PointsService(new TierService);

    expect($service->pointsForAmountCents($cents, $basisPoints))->toBe($expected);
})->with([
    '1.5x on ten units' => [1_000, 15_000, 150],
    '2x on ten units' => [1_000, 20_000, 200],
    'a sub-1x multiplier never reduces points' => [1_000, 5_000, 100],
]);
