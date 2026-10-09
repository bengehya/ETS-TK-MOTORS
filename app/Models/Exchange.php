<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exchange extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'reference',
        'source_currency',
        'source_amount',
        'destination_currency',
        'destination_amount',
        'rate',
        'exchange_rate_id',
        'source_entry_id',
        'destination_entry_id',
        'created_by',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_currency' => Currency::class,
            'source_amount' => 'decimal:2',
            'destination_currency' => Currency::class,
            'destination_amount' => 'decimal:2',
            'rate' => 'decimal:4',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<ExchangeRate, $this>
     */
    public function exchangeRate(): BelongsTo
    {
        return $this->belongsTo(ExchangeRate::class);
    }
}
