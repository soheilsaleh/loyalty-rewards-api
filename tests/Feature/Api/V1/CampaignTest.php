<?php

use App\Models\Campaign;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists only running campaigns for members', function () {
    $running = Campaign::factory()->running()->create(['name' => 'Running', 'slug' => 'running']);
    $running->prizes()->create(['label' => '50 points', 'weight' => 1, 'points' => 50, 'stock' => null]);

    Campaign::factory()->scheduled()->create(['slug' => 'scheduled']);
    Campaign::factory()->ended()->create(['slug' => 'ended']);
    Campaign::factory()->inactive()->create(['slug' => 'inactive']);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/campaigns/active')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'running')
        ->assertJsonPath('data.0.is_running', true)
        ->assertJsonPath('data.0.prizes.0.label', '50 points');
});

it('rejects guests from the active campaigns list', function () {
    $this->getJson('/api/v1/campaigns/active')->assertUnauthorized();
});

it('lets an admin create a campaign and converts the multiplier', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/campaigns', [
        'name' => 'Summer Sale',
        'multiplier' => 1.5,
        'starts_at' => now()->subDay()->toIso8601String(),
        'ends_at' => now()->addWeek()->toIso8601String(),
    ])->assertCreated()
        ->assertJsonPath('data.slug', 'summer-sale')
        ->assertJsonPath('data.multiplier', 1.5)
        ->assertJsonPath('data.multiplier_basis_points', 15_000);

    $this->assertDatabaseHas('campaigns', ['slug' => 'summer-sale', 'multiplier_basis_points' => 15_000]);
});

it('validates the campaign payload', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/campaigns', ['multiplier' => 0.5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'multiplier']);
});

it('forbids non-admins from creating campaigns', function () {
    Sanctum::actingAs(User::factory()->staff()->create());

    $this->postJson('/api/v1/campaigns', ['name' => 'Nope'])
        ->assertForbidden();
});

it('lets an admin update a campaign', function () {
    $campaign = Campaign::factory()->create(['multiplier_basis_points' => 10_000]);
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->putJson("/api/v1/campaigns/{$campaign->getKey()}", ['multiplier' => 3])
        ->assertOk()
        ->assertJsonPath('data.multiplier', 3)
        ->assertJsonPath('data.multiplier_basis_points', 30_000);
});

it('lets an admin delete a campaign', function () {
    $campaign = Campaign::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->deleteJson("/api/v1/campaigns/{$campaign->getKey()}")->assertNoContent();

    $this->assertDatabaseMissing('campaigns', ['id' => $campaign->getKey()]);
});

it('lets an admin add a prize to a campaign', function () {
    $campaign = Campaign::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/prizes", [
        'label' => 'Voucher',
        'weight' => 3,
        'points' => 250,
        'stock' => 10,
    ])->assertCreated()
        ->assertJsonPath('data.label', 'Voucher')
        ->assertJsonPath('data.weight', 3)
        ->assertJsonPath('data.has_stock', true);

    $this->assertDatabaseHas('campaign_prizes', ['campaign_id' => $campaign->getKey(), 'label' => 'Voucher']);
});
