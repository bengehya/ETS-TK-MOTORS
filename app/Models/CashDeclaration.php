<?php

namespace App\Models;

use App\Enums\CashDeclarationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDeclaration extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'previous_declaration_id',
        'note',
        'counted_usd',
        'counted_cdf',
        'book_usd',
        'book_cdf',
        'gap_usd',
        'gap_cdf',
        'reference_rate',
        'rate_effective_at',
        'indicative_usd',
        'status',
        'correction_reason',
        'validated_by',
        'validated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'counted_usd' => 'decimal:2',
            'counted_cdf' => 'decimal:2',
            'book_usd' => 'decimal:2',
            'book_cdf' => 'decimal:2',
            'gap_usd' => 'decimal:2',
            'gap_cdf' => 'decimal:2',
            'reference_rate' => 'decimal:4',
            'rate_effective_at' => 'datetime',
            'indicative_usd' => 'decimal:2',
            'status' => CashDeclarationStatus::class,
            'validated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
