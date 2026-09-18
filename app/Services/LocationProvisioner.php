<?php

namespace App\Services;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\Organization;

class LocationProvisioner
{
    public function provision(Organization $organization): void
    {
        Location::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'type' => LocationType::Boutique,
            ],
            [
                'name' => LocationType::Boutique->label(),
                'slug' => 'boutique',
                'is_sellable' => true,
            ],
        );

        Location::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'type' => LocationType::Depot,
            ],
            [
                'name' => LocationType::Depot->label(),
                'slug' => 'depot',
                'is_sellable' => false,
            ],
        );
    }

    public function boutique(Organization $organization): Location
    {
        $this->provision($organization);

        return Location::query()
            ->where('organization_id', $organization->id)
            ->boutique()
            ->firstOrFail();
    }

    public function depot(Organization $organization): Location
    {
        $this->provision($organization);

        return Location::query()
            ->where('organization_id', $organization->id)
            ->depot()
            ->firstOrFail();
    }
}
