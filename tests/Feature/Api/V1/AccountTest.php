<?php

use App\Models\Account;
use App\Models\User;
use App\Services\PointsService;
use Database\Seeders\TierSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(TierSeeder::class);
});

it('returns the member account with balance and tier', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me/account')
        ->assertOk()
        ->assertJsonPath('data.points_balance', 0)
        ->assertJsonPath('data.lifetime_points', 0)
        ->assertJsonPath('data.currency', 'EUR')
        ->assertJsonPath('data.tier.slug', 'bronze')
        ->assertJsonStructure(['data' => ['id', 'points_balance', 'lifetime_points', 'currency', 'tier']]);
});

it('creates a bronze account on first access when none exists', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me/account')
        ->assertOk()
        ->assertJsonPath('data.tier.slug', 'bronze');

    expect($user->account()->exists())->toBeTrue();
});

it('lists the ledger newest first', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $points = app(PointsService::class);
    $points->earn($account, 100, 'Welcome bonus');
    $points->earn($account, 50, 'Second bonus');

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me/transactions')
        ->assertOk()
        ->assertJsonPath('data.0.points', 50)
        ->assertJsonPath('data.1.points', 100)
        ->assertJsonStructure(['data' => [['id', 'type', 'points', 'balance_after', 'created_at']]]);
});

it('rejects unauthenticated access to the account', function () {
    $this->getJson('/api/v1/me/account')->assertUnauthorized();
});
