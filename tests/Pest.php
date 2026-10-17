<?php

use App\Models\Account;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the full Laravel application and refresh the database
| between tests, so they run against the same schema as production.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a member with an account and an initial batch of earned points.
 *
 * @return array{0: User, 1: Account}
 */
function memberWithPoints(int $points): array
{
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    app(PointsService::class)->earn($account, $points, 'Seed');

    return [$user, $account];
}
