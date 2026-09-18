<?php

namespace App\Models;

use App\Enums\LocationType;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'type',
        'name',
        'slug',
        'is_sellable',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
            'is_sellable' => 'boolean',
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
     * @return HasMany<Inventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function isSellable(): bool
    {
        return $this->is_sellable && $this->type->isSellable();
    }

    public function scopeBoutique($query)
    {
        return $query->where('type', LocationType::Boutique);
    }

    public function scopeDepot($query)
    {
        return $query->where('type', LocationType::Depot);
    }
}
