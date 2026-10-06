<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'reference',
        'amount',
        'reason',
        'spent_on',
        'status',
        'created_by',
        'decided_by',
        'decided_at',
        'decision_note',
        'cash_entry_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'spent_on' => 'date',
            'status' => ExpenseStatus::class,
            'decided_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return BelongsTo<CashEntry, $this>
     */
    public function cashEntry(): BelongsTo
    {
        return $this->belongsTo(CashEntry::class);
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function isPending(): bool
    {
        return $this->status === ExpenseStatus::Pending;
    }
}
