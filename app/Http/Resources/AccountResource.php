<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Account
 */
class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'currency' => config('loyalty.currency.code'),
            'points_balance' => $this->points_balance,
            'lifetime_points' => $this->lifetime_points,
            'tier' => $this->whenLoaded('tier', fn () => $this->tier === null ? null : TierResource::make($this->tier)),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
