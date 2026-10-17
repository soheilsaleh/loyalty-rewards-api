<?php

use App\Models\Account;
use Database\Seeders\TierSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(TierSeeder::class);
    config(['loyalty.webhooks.secret' => 'test-secret']);
    Mail::fake();
});

/**
 * Build a signed webhook request (raw body plus server headers).
 *
 * @param  array<string, mixed>  $payload
 * @return array{0: string, 1: array<string, string>}
 */
function signedWebhook(array $payload, ?int $timestamp = null, string $secret = 'test-secret'): array
{
    $timestamp ??= now()->getTimestamp();
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $server = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, $secret),
    ];

    return [$body, $server];
}

it('registers a purchase from a correctly signed webhook', function () {
    $account = Account::factory()->create();
    [$body, $server] = signedWebhook([
        'account_id' => $account->getKey(),
        'amount_cents' => 2_000,
        'external_id' => 'evt-1',
    ]);

    $this->call('POST', '/api/v1/webhooks/purchase', [], [], [], $server, $body)
        ->assertCreated()
        ->assertJsonPath('data.points_awarded', 200);

    expect($account->refresh()->points_balance)->toBe(200);
});

it('is idempotent when the same event is replayed', function () {
    $account = Account::factory()->create();
    [$body, $server] = signedWebhook([
        'account_id' => $account->getKey(),
        'amount_cents' => 1_000,
        'external_id' => 'evt-2',
    ]);

    $this->call('POST', '/api/v1/webhooks/purchase', [], [], [], $server, $body)->assertCreated();
    $this->call('POST', '/api/v1/webhooks/purchase', [], [], [], $server, $body)->assertOk();

    expect($account->refresh()->points_balance)->toBe(100);
});

it('rejects a webhook with an invalid signature', function () {
    $account = Account::factory()->create();
    [$body, $server] = signedWebhook(
        ['account_id' => $account->getKey(), 'amount_cents' => 1_000, 'external_id' => 'evt-3'],
        secret: 'the-wrong-secret',
    );

    $this->call('POST', '/api/v1/webhooks/purchase', [], [], [], $server, $body)->assertUnauthorized();

    $this->assertDatabaseCount('purchases', 0);
});

it('rejects a webhook whose timestamp is out of tolerance', function () {
    $account = Account::factory()->create();
    [$body, $server] = signedWebhook(
        ['account_id' => $account->getKey(), 'amount_cents' => 1_000, 'external_id' => 'evt-4'],
        timestamp: now()->subHour()->getTimestamp(),
    );

    $this->call('POST', '/api/v1/webhooks/purchase', [], [], [], $server, $body)->assertUnauthorized();
});

it('returns 503 when no signing secret is configured', function () {
    config(['loyalty.webhooks.secret' => '']);

    $account = Account::factory()->create();
    [$body, $server] = signedWebhook([
        'account_id' => $account->getKey(),
        'amount_cents' => 1_000,
        'external_id' => 'evt-5',
    ]);

    $this->call('POST', '/api/v1/webhooks/purchase', [], [], [], $server, $body)->assertStatus(503);
});

it('validates the webhook payload', function () {
    [$body, $server] = signedWebhook(['amount_cents' => 1_000]);

    $this->call('POST', '/api/v1/webhooks/purchase', [], [], [], $server, $body)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['account_id', 'external_id']);
});
