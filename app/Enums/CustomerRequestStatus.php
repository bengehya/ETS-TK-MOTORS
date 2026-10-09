<?php

namespace App\Enums;

enum CustomerRequestStatus: string
{
    case Open = 'open';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Fulfilled => 'Satisfaite',
            self::Cancelled => 'Annulée',
        };
    }
}
