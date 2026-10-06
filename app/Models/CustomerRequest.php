<?php

namespace App\Models;

use App\Enums\CustomerRequestPriority;
use App\Enums\CustomerRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'product_id',
        'recorded_by',
        'customer_name',
        'quantity',
        'priority',
        'frequency',
        'status',
        'notes',
        'requested_at',
        'closed_by',
        'closed_at',
        'closure_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'frequency' => 'integer',
            'priority' => CustomerRequestPriority::class,
            'status' => CustomerRequestStatus::class,
            'requested_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function isOpen(): bool
    {
        return $this->status === CustomerRequestStatus::Open;
    }
}
