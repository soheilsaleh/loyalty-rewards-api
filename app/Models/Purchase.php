<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property PurchaseStatus $status
 * @property int $amount_cents
 * @property int $points_awarded
 * @property Carbon $occurred_at
 * @property Carbon|null $refunded_at
 */
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'account_id',
        'external_id',
        'amount_cents',
        'points_awarded',
        'status',
        'occurred_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'points_awarded' => 'integer',
            'status' => PurchaseStatus::class,
            'occurred_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isRefunded(): bool
    {
        return $this->status === PurchaseStatus::Refunded;
    }
}
