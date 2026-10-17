<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Redeem\RedeemRequest;
use App\Http\Resources\RedemptionResource;
use App\Models\Reward;
use App\Models\User;
use App\Services\AccountService;
use App\Services\RedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RedemptionController extends Controller
{
    public function __construct(
        private readonly AccountService $accounts,
        private readonly RedemptionService $redemptions,
    ) {}

    /**
     * Redeem a reward for the authenticated member.
     *
     * The request must carry a unique `Idempotency-Key` header so a retry
     * returns the original redemption instead of spending points again.
     */
    public function store(RedeemRequest $request, Reward $reward): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $redemption = $this->redemptions->redeem(
            account: $this->accounts->forUser($user),
            reward: $reward,
            idempotencyKey: (string) $request->validated('idempotency_key'),
        );

        $status = $redemption->wasRecentlyCreated
            ? Response::HTTP_CREATED
            : Response::HTTP_OK;

        return RedemptionResource::make($redemption)
            ->response()
            ->setStatusCode($status);
    }
}
