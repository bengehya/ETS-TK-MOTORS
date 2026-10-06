<?php

namespace App\Enums;

enum CustomerRequestPriority: string
{
    case Normal = 'normal';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normale',
            self::Urgent => 'Urgente',
        };
    }
}
