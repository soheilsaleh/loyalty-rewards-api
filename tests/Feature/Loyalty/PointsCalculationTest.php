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
