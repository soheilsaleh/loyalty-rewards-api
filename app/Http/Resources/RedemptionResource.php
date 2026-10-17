<?php

namespace App\Http\Resources;

use App\Models\Redemption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Redemption
 */
class RedemptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'points_spent' => $this->points_spent,
            'status' => $this->status->value,
            'reward' => $this->whenLoaded('reward', fn () => RewardResource::make($this->reward)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
