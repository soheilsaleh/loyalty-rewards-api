<?php

use App\Models\Reward;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists active rewards cheapest first', function () {
    Reward::factory()->create(['name' => 'Expensive', 'slug' => 'expensive', 'cost_points' => 5_000]);
    Reward::factory()->create(['name' => 'Cheap', 'slug' => 'cheap', 'cost_points' => 100]);
    Reward::factory()->inactive()->create(['name' => 'Hidden', 'slug' => 'hidden', 'cost_points' => 50]);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/rewards')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'cheap')
        ->assertJsonPath('data.1.slug', 'expensive')
        ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'cost_points', 'stock', 'is_active']]]);
});

it('rejects guests from the reward catalog', function () {
    $this->getJson('/api/v1/rewards')->assertUnauthorized();
});

it('lets an admin create a reward', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/rewards', [
        'name' => 'Spa Day',
        'cost_points' => 8_000,
        'stock' => 10,
    ])->assertCreated()
        ->assertJsonPath('data.slug', 'spa-day')
        ->assertJsonPath('data.cost_points', 8_000)
        ->assertJsonPath('data.stock', 10);

    $this->assertDatabaseHas('rewards', ['slug' => 'spa-day', 'cost_points' => 8_000, 'stock' => 10]);
});

it('validates the reward payload', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/rewards', ['cost_points' => 0])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'cost_points']);
});

it('forbids non-admins from creating rewards', function () {
    Sanctum::actingAs(User::factory()->staff()->create());

    $this->postJson('/api/v1/rewards', ['name' => 'Nope', 'cost_points' => 100])
        ->assertForbidden();
});

it('lets an admin update a reward', function () {
    $reward = Reward::factory()->create(['cost_points' => 100]);
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->putJson("/api/v1/rewards/{$reward->getKey()}", [
        'cost_points' => 750,
        'is_active' => false,
    ])->assertOk()
        ->assertJsonPath('data.cost_points', 750)
        ->assertJsonPath('data.is_active', false);
});

it('lets an admin delete a reward', function () {
    $reward = Reward::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->deleteJson("/api/v1/rewards/{$reward->getKey()}")->assertNoContent();

    $this->assertDatabaseMissing('rewards', ['id' => $reward->getKey()]);
});
