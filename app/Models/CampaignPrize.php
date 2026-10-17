<?php

namespace App\Models;

use Database\Factories\CampaignPrizeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $weight
 * @property int $points
 * @property int|null $stock
 */
class CampaignPrize extends Model
{
    /** @use HasFactory<CampaignPrizeFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'label',
        'weight',
        'points',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'points' => 'integer',
            'stock' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Whether the prize can still be won (a null stock is unlimited).
     */
    public function hasStock(): bool
    {
        return $this->stock === null || $this->stock > 0;
    }
}
