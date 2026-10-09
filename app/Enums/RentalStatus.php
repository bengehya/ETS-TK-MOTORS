<?php

namespace App\Enums;

enum RentalStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expired => 'Échue',
            self::Closed => 'Clôturée',
        };
    }
}
