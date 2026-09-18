<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\LocationProvisioner;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['slug' => config('tkmotors.organization_slug')],
            ['name' => config('tkmotors.company')],
        );

        app(LocationProvisioner::class)->provision($organization);
    }
}
