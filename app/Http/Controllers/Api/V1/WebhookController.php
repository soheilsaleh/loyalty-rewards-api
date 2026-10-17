<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Models\Account;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class WebhookController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    /**
     * Register a purchase pushed by an external provider.
     *
     * The signature is verified by the webhook.signature middleware and the
     * `external_id` makes the endpoint idempotent: replaying the same event
     * returns the original purchase instead of awarding points twice.
     */
    public function purchase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'external_id' => ['required', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $account = Account::query()->findOrFail($data['account_id']);

        $purchase = $this->purchases->register(
            account: $account,
            amountCents: $data['amount_cents'],
            occurredAt: isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : null,
            externalId: $data['external_id'],
        );

        $status = $purchase->wasRecentlyCreated
            ? Response::HTTP_CREATED
            : Response::HTTP_OK;

        return PurchaseResource::make($purchase)
            ->response()
            ->setStatusCode($status);
    }
}
