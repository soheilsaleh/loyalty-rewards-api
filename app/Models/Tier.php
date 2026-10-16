<?php

namespace App\Models;

use Database\Factories\TierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tier extends Model
{
    /** @use HasFactory<TierFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_lifetime_points',
    ];

    protected function casts(): array
    {
        return [
            'min_lifetime_points' => 'integer',
        ];
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
