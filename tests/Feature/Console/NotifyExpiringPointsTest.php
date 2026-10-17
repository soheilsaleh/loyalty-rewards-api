<?php

use App\Mail\PointsExpiringReminder;
use App\Models\Account;
use App\Models\User;
use App\Services\PointsService;
use Database\Seeders\TierSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(TierSeeder::class);
});

it('queues a reminder for points that expire soon', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'member@loyalty.test']);
    $account = Account::factory()->for($user)->create();
    app(PointsService::class)->earn($account, 400, 'Soon', expiresAt: now()->addDays(5));

    $this->artisan('loyalty:notify-expiring-points')->assertSuccessful();

    Mail::assertSent(PointsExpiringReminder::class, function (PointsExpiringReminder $mail) {
        return $mail->points === 400 && $mail->hasTo('member@loyalty.test');
    });

    expect($account->transactions()->first()->reminder_sent_at)->not->toBeNull();
});

it('does not send a reminder twice for the same batch', function () {
    Mail::fake();

    $account = Account::factory()->create();
    app(PointsService::class)->earn($account, 400, 'Soon', expiresAt: now()->addDays(5));

    $this->artisan('loyalty:notify-expiring-points')->assertSuccessful();
    $this->artisan('loyalty:notify-expiring-points')->assertSuccessful();

    Mail::assertSent(PointsExpiringReminder::class, 1);
});

it('ignores batches that expire beyond the reminder window', function () {
    Mail::fake();

    $account = Account::factory()->create();
    app(PointsService::class)->earn($account, 400, 'Later', expiresAt: now()->addMonths(2));

    $this->artisan('loyalty:notify-expiring-points')->assertSuccessful();

    Mail::assertNotSent(PointsExpiringReminder::class);
});
