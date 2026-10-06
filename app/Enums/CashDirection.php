<?php

namespace App\Enums;

enum CashDirection: string
{
    case Inflow = 'inflow';
    case Outflow = 'outflow';

    public function label(): string
    {
        return match ($this) {
            self::Inflow => 'Entrée',
            self::Outflow => 'Sortie',
        };
    }
}
