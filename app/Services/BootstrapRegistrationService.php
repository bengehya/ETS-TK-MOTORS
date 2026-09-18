<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BootstrapRegistrationService
{
    public function isOpen(): bool
    {
        return ! User::query()->exists();
    }

    public function registerPrincipal(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            if (User::query()->exists()) {
                throw new RuntimeException('Public registration is closed.');
            }

            $organization = Organization::query()->firstOrCreate(
                ['slug' => config('tkmotors.organization_slug')],
                ['name' => config('tkmotors.company')],
            );

            return User::create([
                'organization_id' => $organization->id,
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'role' => Role::BossPrincipal,
            ]);
        });
    }
}
