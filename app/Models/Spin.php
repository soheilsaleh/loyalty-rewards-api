<?php

namespace App\Models;

use Database\Factories\SpinFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $points_awarded
 */
class Spin extends Model
{
    /** @use HasFactory<SpinFactory> */
    use HasFactory;

    protected $fillable = [
        'account_id',
        'campaign_id',
        'campaign_prize_id',
        'points_awarded',
    ];

    protected function casts(): array
    {
        return [
            'points_awarded' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<CampaignPrize, $this>
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(CampaignPrize::class, 'campaign_prize_id');
    }
}
