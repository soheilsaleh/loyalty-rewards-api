<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Register a new customer and issue an API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                ...$request->safe()->only(['name', 'email', 'phone', 'password']),
                'role' => Role::Customer,
            ]);

            $user->account()->create([
                'tier_id' => Tier::query()->orderBy('min_lifetime_points')->value('id'),
            ]);

            return $user;
        });

        return UserResource::make($user)
            ->additional(['token' => $this->issueToken($user)])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Authenticate an existing user and issue a new API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();

        return UserResource::make($user)
            ->additional(['token' => $this->issueToken($user)])
            ->response();
    }

    /**
     * Revoke the token that was used for the current request.
     */
    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    private function issueToken(User $user): string
    {
        return $user->createToken('api-token')->plainTextToken;
    }
}
