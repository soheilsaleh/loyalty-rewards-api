<?php

use App\Enums\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('registers a new customer and returns an api token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '+15551234567',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonPath('data.role', Role::Customer->value)
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role'], 'token']);

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'role' => Role::Customer->value,
    ]);
});

it('rejects registration with invalid data', function () {
    $this->postJson('/api/v1/auth/register', [
        'email' => 'not-an-email',
        'password' => 'short',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('logs in with valid credentials', function () {
    $user = User::factory()->create(['email' => 'john@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ])->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonStructure(['data', 'token']);
});

it('rejects login with a wrong password', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'john@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('throttles login attempts', function () {
    User::factory()->create(['email' => 'rate@example.com']);

    foreach (range(1, 5) as $ignored) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'rate@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'rate@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('rejects unauthenticated access to the profile', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('logs out and revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

    $this->assertDatabaseCount('personal_access_tokens', 0);

    // The auth guard is resolved once per application instance in tests, so it
    // must be forgotten before re-checking that the revoked token is rejected.
    $this->app['auth']->forgetGuards();

    $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
});
