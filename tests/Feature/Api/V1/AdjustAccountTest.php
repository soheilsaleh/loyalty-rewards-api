<?php

use App\Models\Account;
use App\Models\User;
use App\Services\PointsService;
use Database\Seeders\TierSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(TierSeeder::class);
});

it('lets an admin add points and upgrades the tier', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $account = Account::factory()->create();

    $this->postJson("/api/v1/accounts/{$account->getKey()}/adjust", [
        'points' => 1_500,
        'reason' => 'Goodwill credit',
    ])->assertOk()
        ->assertJsonPath('data.points_balance', 1_500)
        ->assertJsonPath('data.lifetime_points', 1_500)
        ->assertJsonPath('data.tier.slug', 'silver');

    $this->assertDatabaseHas('point_transactions', [
        'account_id' => $account->getKey(),
        'type' => 'adjust',
        'points' => 1_500,
        'description' => 'Goodwill credit',
    ]);
});

it('lets an admin remove points', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $account = Account::factory()->create();
    app(PointsService::class)->earn($account, 500, 'Seed');

    $this->postJson("/api/v1/accounts/{$account->getKey()}/adjust", [
        'points' => -200,
        'reason' => 'Correction',
    ])->assertOk()->assertJsonPath('data.points_balance', 300);

    $account->refresh();
    expect($account->points_balance)->toBe(300)
        ->and($account->lifetime_points)->toBe(500);
});

it('prevents the balance from going negative', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $account = Account::factory()->create();

    $this->postJson("/api/v1/accounts/{$account->getKey()}/adjust", [
        'points' => -100,
        'reason' => 'Too much',
    ])->assertStatus(422);
});

it('validates the adjustment payload', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $account = Account::factory()->create();

    $this->postJson("/api/v1/accounts/{$account->getKey()}/adjust", [
        'points' => 0,
    ])->assertStatus(422)->assertJsonValidationErrors(['points', 'reason']);
});

it('forbids non-admins from adjusting accounts', function () {
    $account = Account::factory()->create();

    Sanctum::actingAs(User::factory()->staff()->create());
    $this->postJson("/api/v1/accounts/{$account->getKey()}/adjust", [
        'points' => 100,
        'reason' => 'Nope',
    ])->assertForbidden();

    Sanctum::actingAs($account->user);
    $this->postJson("/api/v1/accounts/{$account->getKey()}/adjust", [
        'points' => 100,
        'reason' => 'Nope',
    ])->assertForbidden();
});
