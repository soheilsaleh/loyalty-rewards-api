<?php

namespace App\Http\Resources;

use App\Models\Tier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tier
 */
class TierResource extends JsonResource
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
            'min_lifetime_points' => $this->min_lifetime_points,
        ];
    }
}
