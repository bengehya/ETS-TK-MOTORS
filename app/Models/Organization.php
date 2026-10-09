<?php

namespace App\Models;

use App\Services\LocationProvisioner;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'maintenance_enabled' => 'boolean',
            'maintenance_started_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Organization $organization): void {
            app(LocationProvisioner::class)->provision($organization);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function maintenanceStarter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maintenance_started_by');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Arrival, $this>
     */
    public function arrivals(): HasMany
    {
        return $this->hasMany(Arrival::class);
    }
}
