<?php

namespace App\Models;

use App\Enums\CustomerRequestPriority;
use App\Enums\RestockSuggestionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestockSuggestion extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'product_id',
        'designation',
        'subject_key',
        'request_count',
        'total_quantity',
        'observation_days',
        'period_started_at',
        'last_requested_at',
        'boutique_quantity',
        'depot_quantity',
        'priority',
        'status',
        'justification',
        'decided_by',
        'decided_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_count' => 'integer',
            'total_quantity' => 'integer',
            'observation_days' => 'integer',
            'period_started_at' => 'datetime',
            'last_requested_at' => 'datetime',
            'boutique_quantity' => 'integer',
            'depot_quantity' => 'integer',
            'priority' => CustomerRequestPriority::class,
            'status' => RestockSuggestionStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }
}
