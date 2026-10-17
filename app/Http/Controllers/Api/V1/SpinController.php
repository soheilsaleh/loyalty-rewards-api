<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpinResource;
use App\Models\Campaign;
use App\Models\User;
use App\Services\AccountService;
use App\Services\SpinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SpinController extends Controller
{
    public function __construct(
        private readonly AccountService $accounts,
        private readonly SpinService $spins,
    ) {}

    /**
     * Spin a campaign's wheel for the authenticated member.
     *
     * A member can spin each campaign once; a repeated request conflicts.
     */
    public function store(Request $request, Campaign $campaign): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $spin = $this->spins->spin($this->accounts->forUser($user), $campaign);

        return SpinResource::make($spin)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
