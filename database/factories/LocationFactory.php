<?php

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'type' => LocationType::Boutique,
            'name' => LocationType::Boutique->label(),
            'slug' => 'boutique',
            'is_sellable' => true,
        ];
    }
}
