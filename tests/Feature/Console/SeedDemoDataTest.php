<?php

use App\Models\Account;
use App\Models\User;

it('generates the requested number of members with ledger entries', function () {
    $this->artisan('loyalty:seed-demo', ['--members' => 5])->assertSuccessful();

    expect(Account::query()->count())->toBe(5)
        ->and(User::query()->where('role', 'customer')->count())->toBe(5);

    $this->assertDatabaseCount('point_transactions', 15);
});
