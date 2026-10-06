<?php

namespace App\Models;

use App\Enums\RentalStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rental extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'label',
        'amount',
        'started_on',
        'duration_months',
        'ends_on',
        'status',
        'recorded_by',
        'closed_by',
        'closed_at',
        'closure_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'started_on' => 'date',
            'duration_months' => 'integer',
            'ends_on' => 'date',
            'status' => RentalStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function remainingMonths(?CarbonInterface $today = null): int
    {
        $today = ($today ?? now())->startOfDay();
        $end = $this->ends_on?->startOfDay();

        if ($end === null || $end->lte($today)) {
            return 0;
        }

        $months = 0;
        $cursor = $today->copy();

        while ($months < 240 && $cursor->copy()->addMonth()->lte($end)) {
            $cursor = $cursor->addMonth();
            $months++;
        }

        return $months;
    }

    public function isClosed(): bool
    {
        return $this->status === RentalStatus::Closed;
    }
}
