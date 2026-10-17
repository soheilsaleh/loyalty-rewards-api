<?php

use App\Models\Account;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\User;
use App\Services\PointsService;
use Database\Seeders\TierSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(TierSeeder::class);
});

it('redeems a reward and spends points', function () {
    [$user, $account] = memberWithPoints(1_000);
    $reward = Reward::factory()->create(['slug' => 'coffee', 'cost_points' => 400, 'stock' => 5]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/rewards/{$reward->getKey()}/redeem", [], ['Idempotency-Key' => 'key-1'])
        ->assertCreated()
        ->assertJsonPath('data.points_spent', 400)
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.reward.slug', 'coffee');

    expect($account->refresh()->points_balance)->toBe(600)
        ->and($reward->refresh()->stock)->toBe(4);

    $this->assertDatabaseHas('point_transactions', [
        'account_id' => $account->getKey(),
        'type' => 'redeem',
        'points' => -400,
        'balance_after' => 600,
    ]);
});

it('requires an idempotency key', function () {
    [$user] = memberWithPoints(1_000);
    $reward = Reward::factory()->create(['cost_points' => 400]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/rewards/{$reward->getKey()}/redeem")
        ->assertStatus(422)
        ->assertJsonValidationErrors('idempotency_key');
});

it('rejects redemption without enough points', function () {
    [$user] = memberWithPoints(100);
    $reward = Reward::factory()->create(['cost_points' => 400]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/rewards/{$reward->getKey()}/redeem", [], ['Idempotency-Key' => 'low'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'The account does not have enough points.');
});

it('rejects redemption of an inactive reward', function () {
    [$user] = memberWithPoints(1_000);
    $reward = Reward::factory()->inactive()->create(['cost_points' => 100]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/rewards/{$reward->getKey()}/redeem", [], ['Idempotency-Key' => 'inactive'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'This reward is not available.');
});

it('rejects redemption of an out of stock reward', function () {
    [$user] = memberWithPoints(1_000);
    $reward = Reward::factory()->create(['cost_points' => 100, 'stock' => 0]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/rewards/{$reward->getKey()}/redeem", [], ['Idempotency-Key' => 'empty'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'This reward is out of stock.');
});

it('returns the original redemption for a repeated idempotency key', function () {
    [$user, $account] = memberWithPoints(1_000);
    $reward = Reward::factory()->create(['cost_points' => 400]);
    $url = "/api/v1/rewards/{$reward->getKey()}/redeem";

    Sanctum::actingAs($user);

    $first = $this->postJson($url, [], ['Idempotency-Key' => 'same-key'])->assertCreated();
    $second = $this->postJson($url, [], ['Idempotency-Key' => 'same-key'])->assertOk();

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and($account->refresh()->points_balance)->toBe(600)
        ->and(Redemption::query()->where('account_id', $account->getKey())->count())->toBe(1);
});

it('conflicts when an idempotency key is reused for another reward', function () {
    [$user] = memberWithPoints(1_000);
    $first = Reward::factory()->create(['cost_points' => 100]);
    $second = Reward::factory()->create(['cost_points' => 100]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/rewards/{$first->getKey()}/redeem", [], ['Idempotency-Key' => 'shared'])
        ->assertCreated();

    $this->postJson("/api/v1/rewards/{$second->getKey()}/redeem", [], ['Idempotency-Key' => 'shared'])
        ->assertStatus(409);
});

it('consumes the oldest points first', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $points = app(PointsService::class);
    $oldest = $points->earn($account, 100, 'Oldest');
    $newest = $points->earn($account, 200, 'Newest');

    $reward = Reward::factory()->create(['cost_points' => 150]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/rewards/{$reward->getKey()}/redeem", [], ['Idempotency-Key' => 'fifo'])
        ->assertCreated();

    expect($oldest->refresh()->points_remaining)->toBe(0)
        ->and($newest->refresh()->points_remaining)->toBe(150);
});

it('rejects guests from redeeming', function () {
    $reward = Reward::factory()->create(['cost_points' => 100]);

    $this->postJson("/api/v1/rewards/{$reward->getKey()}/redeem", [], ['Idempotency-Key' => 'guest'])
        ->assertUnauthorized();
});
