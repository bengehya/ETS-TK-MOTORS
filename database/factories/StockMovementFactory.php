<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
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
            'type' => StockMovementType::Receipt,
            'quantity' => 1,
            'quantity_before' => 0,
            'quantity_after' => 1,
            'user_id' => User::factory(),
            'notes' => null,
        ];
    }
}
