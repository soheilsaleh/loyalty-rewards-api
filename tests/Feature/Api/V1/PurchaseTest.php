<?php

use App\Models\Account;
use App\Models\User;
use App\Services\PurchaseService;
use Database\Seeders\TierSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(TierSeeder::class);
});

it('registers a purchase and awards points', function () {
    $staff = User::factory()->staff()->create();
    $account = Account::factory()->create();

    Sanctum::actingAs($staff);

    $this->postJson('/api/v1/purchases', [
        'account_id' => $account->getKey(),
        'amount_cents' => 2_500,
        'external_id' => 'order-1',
    ])->assertCreated()
        ->assertJsonPath('data.points_awarded', 250)
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.external_id', 'order-1');

    $account->refresh();

    expect($account->points_balance)->toBe(250)
        ->and($account->lifetime_points)->toBe(250);

    $this->assertDatabaseHas('point_transactions', [
        'account_id' => $account->getKey(),
        'type' => 'earn',
        'points' => 250,
        'balance_after' => 250,
    ]);
});

it('validates the purchase payload', function () {
    Sanctum::actingAs(User::factory()->staff()->create());

    $this->postJson('/api/v1/purchases', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['account_id', 'amount_cents']);
});

it('forbids customers from registering purchases', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/purchases', [
        'account_id' => Account::factory()->create()->getKey(),
        'amount_cents' => 1_000,
    ])->assertForbidden();
});

it('rejects guests from registering purchases', function () {
    $this->postJson('/api/v1/purchases', [
        'account_id' => 1,
        'amount_cents' => 1_000,
    ])->assertUnauthorized();
});

it('refunds a purchase and reverses the awarded points', function () {
    $staff = User::factory()->staff()->create();
    $account = Account::factory()->create();
    $purchase = app(PurchaseService::class)->register($account, 2_500);

    Sanctum::actingAs($staff);

    $this->postJson("/api/v1/purchases/{$purchase->getKey()}/refund")
        ->assertOk()
        ->assertJsonPath('data.status', 'refunded');

    $account->refresh();

    expect($account->points_balance)->toBe(0)
        ->and($account->lifetime_points)->toBe(0);

    $this->assertDatabaseHas('point_transactions', [
        'account_id' => $account->getKey(),
        'type' => 'refund',
        'points' => -250,
    ]);
});

it('rejects refunding the same purchase twice', function () {
    Sanctum::actingAs(User::factory()->staff()->create());
    $account = Account::factory()->create();
    $purchase = app(PurchaseService::class)->register($account, 2_500);

    $this->postJson("/api/v1/purchases/{$purchase->getKey()}/refund")->assertOk();
    $this->postJson("/api/v1/purchases/{$purchase->getKey()}/refund")
        ->assertStatus(422)
        ->assertJsonPath('message', 'This purchase has already been refunded.');
});
