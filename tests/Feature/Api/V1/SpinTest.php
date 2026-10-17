<?php

use App\Models\Campaign;
use Laravel\Sanctum\Sanctum;

it('awards the prize points and records the spin', function () {
    [$user, $account] = memberWithAccount();
    $campaign = Campaign::factory()->running()->create();
    $campaign->prizes()->create(['label' => '200 points', 'weight' => 1, 'points' => 200, 'stock' => null]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/spin")
        ->assertCreated()
        ->assertJsonPath('data.points_awarded', 200)
        ->assertJsonPath('data.prize.label', '200 points');

    expect($account->refresh()->points_balance)->toBe(200);

    $this->assertDatabaseHas('point_transactions', [
        'account_id' => $account->getKey(),
        'type' => 'bonus',
        'points' => 200,
    ]);
    $this->assertDatabaseHas('spins', [
        'account_id' => $account->getKey(),
        'campaign_id' => $campaign->getKey(),
        'points_awarded' => 200,
    ]);
});

it('stops a member from spinning the same campaign twice', function () {
    [$user] = memberWithAccount();
    $campaign = Campaign::factory()->running()->create();
    $campaign->prizes()->create(['label' => '50 points', 'weight' => 1, 'points' => 50, 'stock' => null]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/spin")->assertCreated();
    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/spin")->assertStatus(409);
});

it('rejects spinning a campaign that is not running', function () {
    [$user] = memberWithAccount();
    $campaign = Campaign::factory()->ended()->create();
    $campaign->prizes()->create(['label' => '50 points', 'weight' => 1, 'points' => 50, 'stock' => null]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/spin")
        ->assertStatus(422)
        ->assertJsonPath('message', 'This campaign is not active.');
});

it('rejects spinning a campaign with no prizes', function () {
    [$user] = memberWithAccount();
    $campaign = Campaign::factory()->running()->create();

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/spin")
        ->assertStatus(422)
        ->assertJsonPath('message', 'This campaign has no prizes available.');
});

it('records a zero-point spin without touching the ledger', function () {
    [$user, $account] = memberWithAccount();
    $campaign = Campaign::factory()->running()->create();
    $campaign->prizes()->create(['label' => 'Try again', 'weight' => 1, 'points' => 0, 'stock' => null]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/spin")
        ->assertCreated()
        ->assertJsonPath('data.points_awarded', 0);

    expect($account->refresh()->points_balance)->toBe(0);
    $this->assertDatabaseMissing('point_transactions', [
        'account_id' => $account->getKey(),
        'type' => 'bonus',
    ]);
});

it('skips prizes that are out of stock', function () {
    [$user] = memberWithAccount();
    $campaign = Campaign::factory()->running()->create();
    $campaign->prizes()->create(['label' => 'Sold out', 'weight' => 5, 'points' => 100, 'stock' => 0]);
    $campaign->prizes()->create(['label' => 'In stock', 'weight' => 1, 'points' => 50, 'stock' => 5]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/spin")
        ->assertCreated()
        ->assertJsonPath('data.prize.label', 'In stock');
});

it('rejects spinning when every prize is out of stock', function () {
    [$user] = memberWithAccount();
    $campaign = Campaign::factory()->running()->create();
    $campaign->prizes()->create(['label' => 'Sold out', 'weight' => 1, 'points' => 100, 'stock' => 0]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/spin")
        ->assertStatus(422)
        ->assertJsonPath('message', 'This campaign has no prizes available.');
});

it('decrements the stock of a limited prize', function () {
    [$user] = memberWithAccount();
    $campaign = Campaign::factory()->running()->create();
    $prize = $campaign->prizes()->create(['label' => 'Limited', 'weight' => 1, 'points' => 100, 'stock' => 2]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/spin")->assertCreated();

    expect($prize->refresh()->stock)->toBe(1);
});

it('rejects guests from spinning', function () {
    $campaign = Campaign::factory()->running()->create();

    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/spin")->assertUnauthorized();
});
