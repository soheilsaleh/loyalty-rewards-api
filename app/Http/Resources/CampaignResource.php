<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Campaign
 */
class CampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'multiplier' => $this->multiplierValue(),
            'multiplier_basis_points' => $this->multiplier_basis_points,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'is_running' => $this->isRunning(),
            'prizes' => CampaignPrizeResource::collection($this->whenLoaded('prizes')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
