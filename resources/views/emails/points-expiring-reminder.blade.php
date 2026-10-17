<x-mail::message>
# Your points are about to expire

You have **{{ number_format($points) }} points** that will expire on {{ $expiresAt->toFormattedDateString() }}.

Redeem them before they are gone.

<x-mail::button :url="config('app.url')">
Browse rewards
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
