<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'product_id' => Product::factory(),
            'location_id' => Location::factory(),
            'quantity' => 0,
        ];
    }
}
