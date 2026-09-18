<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'code',
        'barcode',
        'name',
        'category',
        'description',
        'sale_price',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $product->code = strtoupper(trim($product->code));
            $product->barcode = self::normalizeBarcode($product->barcode);
        });

        static::updating(function (Product $product): void {
            if ($product->isDirty('code')) {
                $product->code = strtoupper(trim($product->code));
            }

            if ($product->isDirty('barcode')) {
                $product->barcode = self::normalizeBarcode($product->barcode);
            }
        });

        static::created(function (Product $product): void {
            app(\App\Services\LocationProvisioner::class)->provision($product->organization);
            app(\App\Services\InventoryService::class)->initializeForProduct($product);
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Inventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $inner) use ($like): void {
            $inner->where('name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('barcode', 'like', $like);
        });
    }

    public static function normalizeBarcode(?string $barcode): ?string
    {
        $barcode = trim((string) $barcode);

        return $barcode === '' ? null : $barcode;
    }
}
