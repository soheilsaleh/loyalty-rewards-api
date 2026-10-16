<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AdjustAccountRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\PointTransactionResource;
use App\Models\Account;
use App\Models\Tier;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AccountController extends Controller
{
    public function __construct(private readonly PointsService $points) {}

    /**
     * The authenticated member's account with its tier and balance.
     */
    public function show(Request $request): JsonResponse
    {
        $account = $this->accountFor($request)->load('tier');

        return AccountResource::make($account)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * The authenticated member's ledger, newest first.
     */
    public function transactions(Request $request): AnonymousResourceCollection
    {
        $transactions = $this->accountFor($request)
            ->transactions()
            ->latest('id')
            ->cursorPaginate(15);

        return PointTransactionResource::collection($transactions);
    }

    /**
     * Apply a manual adjustment to any member's account (admin only).
     */
    public function adjust(AdjustAccountRequest $request, Account $account): AccountResource
    {
        $this->points->adjust(
            account: $account,
            points: (int) $request->validated('points'),
            description: (string) $request->validated('reason'),
        );

        return AccountResource::make($account->refresh()->load('tier'));
    }

    private function accountFor(Request $request): Account
    {
        /** @var User $user */
        $user = $request->user();

        return $user->account()->firstOrCreate([], [
            'tier_id' => Tier::query()->orderBy('min_lifetime_points')->value('id'),
        ]);
    }
}
