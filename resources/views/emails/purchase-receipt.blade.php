<x-mail::message>
# Thanks for your purchase!

You earned **{{ number_format($purchase->points_awarded) }} points**.

Your purchase of {{ number_format($purchase->amount_cents / 100, 2) }} {{ config('loyalty.currency.code') }} was recorded on {{ $purchase->occurred_at->toFormattedDateString() }}.

Keep collecting points to reach your next tier.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
