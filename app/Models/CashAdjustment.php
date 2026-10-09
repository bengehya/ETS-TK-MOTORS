<?php

namespace App\Models;

use App\Enums\CashDirection;
use App\Enums\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAdjustment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'currency',
        'direction',
        'amount',
        'reason',
        'user_id',
        'cash_entry_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'direction' => CashDirection::class,
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CashEntry, $this>
     */
    public function cashEntry(): BelongsTo
    {
        return $this->belongsTo(CashEntry::class);
    }
}
