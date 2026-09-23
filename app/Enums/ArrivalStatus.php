<?php

namespace App\Enums;

enum ArrivalStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Validated => 'Validé',
            self::Rejected => 'Rejeté',
        };
    }
}
