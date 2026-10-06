<?php

namespace App\Models;

use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'reference',
        'product_id',
        'location_id',
        'seller_id',
        'quantity',
        'unit_sale_price',
        'unit_purchase_cost',
        'line_total',
        'cost_total',
        'profit',
        'status',
        'sold_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_sale_price' => 'decimal:2',
            'unit_purchase_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
            'cost_total' => 'decimal:2',
            'profit' => 'decimal:2',
            'status' => SaleStatus::class,
            'sold_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::Completed);
    }

    public function isCancelled(): bool
    {
        return $this->status === SaleStatus::Cancelled;
    }
}
