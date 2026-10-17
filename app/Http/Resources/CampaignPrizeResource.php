<?php

namespace App\Http\Resources;

use App\Models\CampaignPrize;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CampaignPrize
 */
class CampaignPrizeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'weight' => $this->weight,
            'points' => $this->points,
            'stock' => $this->stock,
            'has_stock' => $this->hasStock(),
        ];
    }
}
