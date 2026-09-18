<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Employe,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function bossPrincipal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::BossPrincipal,
        ]);
    }

    public function bossSecondaire(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::BossSecondaire,
        ]);
    }

    public function employe(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::Employe,
        ]);
    }
}
