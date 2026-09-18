<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->firstOrCreate(
            ['slug' => config('tkmotors.organization_slug')],
            ['name' => config('tkmotors.company')],
        );
    }
}
