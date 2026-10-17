<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignPrize;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CampaignService
{
    /**
     * 1x expressed in basis points, so multipliers stay integer.
     */
    public const MULTIPLIER_BASIS_POINTS = 10_000;

    /**
     * Campaigns that are currently running, soonest to end first.
     *
     * @return Collection<int, Campaign>
     */
    public function active(?Carbon $at = null): Collection
    {
        return Campaign::query()
            ->active($at)
            ->with('prizes')
            ->orderBy('ends_at')
            ->get();
    }

    /**
     * The highest multiplier among running campaigns (1x when there is none).
     */
    public function highestMultiplierBasisPoints(?Carbon $at = null): int
    {
        $max = Campaign::query()->active($at)->max('multiplier_basis_points');

        return max((int) $max, self::MULTIPLIER_BASIS_POINTS);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Campaign
    {
        $data = $this->normalize($data);
        $data['slug'] ??= $this->uniqueSlug((string) $data['name']);
        $data['is_active'] ??= true;

        return Campaign::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Campaign $campaign, array $data): Campaign
    {
        $campaign->update($this->normalize($data));

        return $campaign;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addPrize(Campaign $campaign, array $data): CampaignPrize
    {
        return $campaign->prizes()->create($data);
    }

    /**
     * Translate the human-friendly `multiplier` input (1.5) into basis points.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if (array_key_exists('multiplier', $data)) {
            $data['multiplier_basis_points'] = (int) round(
                ((float) $data['multiplier']) * self::MULTIPLIER_BASIS_POINTS,
            );
            unset($data['multiplier']);
        }

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);

        while (Campaign::query()->where('slug', $slug)->exists()) {
            $slug = Str::slug($name).'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
