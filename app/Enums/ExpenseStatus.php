<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Validated => 'Validée',
            self::Refused => 'Refusée',
        };
    }
}
