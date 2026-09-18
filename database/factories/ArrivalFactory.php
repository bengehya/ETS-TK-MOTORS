<?php

namespace Database\Factories;

use App\Enums\ArrivalStatus;
use App\Models\Arrival;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Arrival>
 */
class ArrivalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'product_id' => Product::factory(),
            'location_id' => function (array $attributes) {
                return Location::query()
                    ->where('organization_id', $attributes['organization_id'])
                    ->where('type', \App\Enums\LocationType::Depot)
                    ->value('id');
            },
            'quantity' => 1,
            'supplier_reference' => null,
            'recorded_by' => User::factory(),
            'status' => ArrivalStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ArrivalStatus::Pending,
        ]);
    }
}
