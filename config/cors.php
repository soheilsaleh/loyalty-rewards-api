<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     | Comma-separated list of browser origins allowed to call the API. The
     | default targets the Vite dev server used by the companion SPA client
     | (https://github.com/soheilsaleh/loyalty-rewards-web).
     */
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173')),
    ))),

    'allowed_origins_patterns' => [],

    // '*' permits the Authorization header and the Idempotency-Key header used
    // by reward redemption.
    'allowed_headers' => ['*'],

    // Let the browser read rate-limit headers so the SPA can react to 429s.
    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
    ],

    'max_age' => 0,

    // The API authenticates with bearer tokens, not cookies, so credentials
    // must stay disabled (a wildcard origin cannot be combined with them).
    'supports_credentials' => false,

];
