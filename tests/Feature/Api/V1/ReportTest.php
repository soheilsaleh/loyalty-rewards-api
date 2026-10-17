<?php

use App\Models\Account;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

it('returns the program summary to admins', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson('/api/v1/reports/summary')
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'generated_at',
            'members',
            'points_outstanding',
            'points_issued',
            'points_redeemed',
            'points_expired',
            'redemptions',
            'purchases',
            'purchase_amount_cents',
            'active_rewards',
            'active_campaigns',
        ]]);
});

it('forbids customers from the summary', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/reports/summary')->assertForbidden();
});

it('caches the summary after the first request', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    expect(Cache::has(ReportService::CACHE_KEY))->toBeFalse();

    $this->getJson('/api/v1/reports/summary')->assertOk();

    expect(Cache::has(ReportService::CACHE_KEY))->toBeTrue();
});

it('serves the summary from cache until it is refreshed', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    Account::factory()->create();

    $first = $this->getJson('/api/v1/reports/summary')->json('data.members');

    Account::factory()->create();

    expect($this->getJson('/api/v1/reports/summary')->json('data.members'))->toBe($first);

    app(ReportService::class)->refresh();

    $this->getJson('/api/v1/reports/summary')
        ->assertJsonPath('data.members', $first + 1);
});
