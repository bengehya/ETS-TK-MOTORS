<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => strtoupper(fake()->unique()->bothify('ART-####')),
            'barcode' => null,
            'name' => fake()->unique()->words(3, true),
            'category' => fake()->randomElement(['Pièces moteur', 'Freinage', 'Éclairage', 'Lubrifiants', 'Accessoires']),
            'description' => fake()->optional()->sentence(),
            'sale_price' => fake()->randomFloat(2, 1, 500),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
