<?php

namespace App\Enums;

enum LocationType: string
{
    case Boutique = 'BOUTIQUE';
    case Depot = 'DEPOT';

    public function label(): string
    {
        return match ($this) {
            self::Boutique => 'Boutique',
            self::Depot => 'Dépôt',
        };
    }

    public function isSellable(): bool
    {
        return $this === self::Boutique;
    }
}
