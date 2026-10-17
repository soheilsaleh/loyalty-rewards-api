<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    /**
     * Verify the HMAC-SHA256 signature of an inbound webhook.
     *
     * The signed payload is "{timestamp}.{raw body}" and the expected signature
     * is sent in the `X-Signature` header (hex). Requests whose timestamp is
     * outside the configured tolerance are rejected to stop replays.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('loyalty.webhooks.secret');

        if ($secret === '') {
            abort(503, 'Webhook signing secret is not configured.');
        }

        $signature = (string) $request->header('X-Signature', '');
        $timestamp = (string) $request->header('X-Timestamp', '');

        if ($signature === '' || $timestamp === '') {
            abort(401, 'Missing webhook signature.');
        }

        $tolerance = (int) config('loyalty.webhooks.tolerance_seconds');

        if (abs(now()->getTimestamp() - (int) $timestamp) > $tolerance) {
            abort(401, 'Webhook timestamp is out of tolerance.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            abort(401, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
