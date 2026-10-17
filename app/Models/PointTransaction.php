<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\PointTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * An immutable entry in the points ledger.
 *
 * Rows are insert-only: the account balance is always the sum of `points`.
 *
 * @property TransactionType $type
 * @property int $points
 * @property int $balance_after
 * @property int $points_remaining
 * @property Carbon|null $expires_at
 * @property Carbon|null $reminder_sent_at
 */
class PointTransaction extends Model
{
    /** @use HasFactory<PointTransactionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'account_id',
        'type',
        'points',
        'balance_after',
        'description',
        'points_remaining',
        'expires_at',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'points' => 'integer',
            'balance_after' => 'integer',
            'points_remaining' => 'integer',
            'expires_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'created_at' => 'datetime',
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
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
