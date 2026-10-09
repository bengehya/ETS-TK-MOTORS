<?php

namespace App\Enums;

enum CashDeclarationStatus: string
{
    case Declared = 'declared';
    case Validated = 'validated';
    case Corrected = 'corrected';

    public function label(): string
    {
        return match ($this) {
            self::Declared => 'Déclarée',
            self::Validated => 'Validée',
            self::Corrected => 'Correction',
        };
    }
}
