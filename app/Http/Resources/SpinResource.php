<?php

namespace App\Http\Resources;

use App\Models\Spin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Spin
 */
class SpinResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'points_awarded' => $this->points_awarded,
            'prize' => CampaignPrizeResource::make($this->whenLoaded('prize')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
