<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | All monetary values are stored as integer cents. The currency code is
    | only used for display purposes.
    |
    */

    'currency' => [
        'code' => env('LOYALTY_CURRENCY', 'EUR'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Points earning
    |--------------------------------------------------------------------------
    |
    | `earn_rate` is the number of points awarded per one major currency unit
    | (100 cents). For example, a rate of 10 awards 10 points for every 1.00
    | spent, and points are floored to the nearest whole point.
    |
    | `expiry_months` is how long an earned batch of points stays valid before
    | it is expired by the scheduled job (FIFO consumption happens first).
    |
    */

    'points' => [
        'earn_rate' => (int) env('LOYALTY_EARN_RATE', 10),
        'expiry_months' => (int) env('LOYALTY_EXPIRY_MONTHS', 12),
        'reminder_days' => (int) env('LOYALTY_EXPIRY_REMINDER_DAYS', 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tiers
    |--------------------------------------------------------------------------
    |
    | Ordered by `min_lifetime_points` ascending. A member belongs to the
    | highest tier whose threshold they have reached.
    |
    */

    'tiers' => [
        ['slug' => 'bronze', 'name' => 'Bronze', 'min_lifetime_points' => 0],
        ['slug' => 'silver', 'name' => 'Silver', 'min_lifetime_points' => 1_000],
        ['slug' => 'gold', 'name' => 'Gold', 'min_lifetime_points' => 5_000],
        ['slug' => 'platinum', 'name' => 'Platinum', 'min_lifetime_points' => 20_000],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | Inbound webhooks are signed with HMAC-SHA256 over "timestamp.body" using
    | the shared secret below. Requests whose timestamp is outside the
    | tolerance window (in seconds) are rejected to mitigate replay attacks.
    |
    */

    'webhooks' => [
        'secret' => env('LOYALTY_WEBHOOK_SECRET', ''),
        'tolerance_seconds' => (int) env('LOYALTY_WEBHOOK_TOLERANCE', 300),
    ],

];
