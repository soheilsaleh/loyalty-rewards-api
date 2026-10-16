<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Account;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    /**
     * Register a purchase and award its points (staff or admin).
     */
    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $account = Account::query()->findOrFail($request->integer('account_id'));

        $externalId = $request->string('external_id')->trim()->toString();

        $purchase = $this->purchases->register(
            account: $account,
            amountCents: $request->integer('amount_cents'),
            occurredAt: $request->date('occurred_at'),
            externalId: $externalId !== '' ? $externalId : null,
        );

        return PurchaseResource::make($purchase)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Refund a purchase and take back its points (staff or admin).
     */
    public function refund(Purchase $purchase): PurchaseResource
    {
        return PurchaseResource::make($this->purchases->refund($purchase));
    }
}
