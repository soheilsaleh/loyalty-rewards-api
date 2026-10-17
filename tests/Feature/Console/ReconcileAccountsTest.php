<?php

use App\Models\Account;
use App\Services\PointsService;

it('passes when every balance matches its ledger', function () {
    $account = Account::factory()->create();
    app(PointsService::class)->earn($account, 100, 'Seed');

    $this->artisan('loyalty:reconcile')->assertSuccessful();
});

it('fails when a balance drifts from its ledger', function () {
    $account = Account::factory()->create();
    app(PointsService::class)->earn($account, 100, 'Seed');
    $account->update(['points_balance' => 999]);

    $this->artisan('loyalty:reconcile')->assertFailed();
});

it('fixes a drifted balance with the --fix option', function () {
    $account = Account::factory()->create();
    app(PointsService::class)->earn($account, 100, 'Seed');
    $account->update(['points_balance' => 999]);

    $this->artisan('loyalty:reconcile', ['--fix' => true])->assertFailed();

    expect($account->refresh()->points_balance)->toBe(100);
});
