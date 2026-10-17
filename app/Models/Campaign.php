<?php

namespace App\Models;

use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $multiplier_basis_points
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 */
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'multiplier_basis_points',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'multiplier_basis_points' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CampaignPrize, $this>
     */
    public function prizes(): HasMany
    {
        return $this->hasMany(CampaignPrize::class);
    }

    /**
     * @return HasMany<Spin, $this>
     */
    public function spins(): HasMany
    {
        return $this->hasMany(Spin::class);
    }

    /**
     * Campaigns that are enabled and inside their time window.
     *
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    public function scopeActive(Builder $query, ?Carbon $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) use ($at) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at);
            })
            ->where(function (Builder $query) use ($at) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $at);
            });
    }

    /**
     * Whether this campaign is currently running.
     */
    public function isRunning(?Carbon $at = null): bool
    {
        $at ??= now();

        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->greaterThan($at)) {
            return false;
        }

        return ! ($this->ends_at !== null && $this->ends_at->lessThan($at));
    }

    /**
     * The points multiplier as a human-friendly decimal (15_000 → 1.5).
     */
    public function multiplierValue(): float
    {
        return round($this->multiplier_basis_points / 10_000, 2);
    }
}
