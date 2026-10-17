<?php

use App\Models\Account;
use App\Models\User;
use App\Services\PointsService;
use Database\Seeders\TierSeeder;

beforeEach(function () {
    $this->seed(TierSeeder::class);
});

it('expires batches that are past their expiry date', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $points = app(PointsService::class);

    $points->earn($account, 500, 'Old', expiresAt: now()->subDay());
    $points->earn($account, 300, 'Fresh', expiresAt: now()->addMonth());

    $this->artisan('loyalty:expire-points')->assertSuccessful();

    expect($account->refresh()->points_balance)->toBe(300);

    $this->assertDatabaseHas('point_transactions', [
        'account_id' => $account->getKey(),
        'type' => 'expire',
        'points' => -500,
    ]);
});

it('does not expire batches that are still valid', function () {
    $account = Account::factory()->create();
    $points = app(PointsService::class);

    $points->earn($account, 400, 'Fresh', expiresAt: now()->addMonth());

    $this->artisan('loyalty:expire-points')->assertSuccessful();

    expect($account->refresh()->points_balance)->toBe(400);

    $this->assertDatabaseMissing('point_transactions', [
        'account_id' => $account->getKey(),
        'type' => 'expire',
    ]);
});

it('leaves the balance and lifetime points consistent after expiry', function () {
    $account = Account::factory()->create();
    $points = app(PointsService::class);

    $points->earn($account, 250, 'Old', expiresAt: now()->subDay());

    $this->artisan('loyalty:expire-points')->assertSuccessful();

    $account->refresh();

    expect($account->points_balance)->toBe(0)
        ->and($account->lifetime_points)->toBe(250);
});
