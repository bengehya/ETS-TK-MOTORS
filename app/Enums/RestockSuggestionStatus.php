<?php

namespace App\Enums;

enum RestockSuggestionStatus: string
{
    case Open = 'open';
    case Reviewed = 'reviewed';
    case Retained = 'retained';
    case Rejected = 'rejected';
    case Decided = 'decided';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'À étudier',
            self::Reviewed => 'Étudiée',
            self::Retained => 'Retenue',
            self::Rejected => 'Rejetée',
            self::Decided => 'Décision de réapprovisionnement',
        };
    }

    public function visibleToStaff(): bool
    {
        return $this === self::Retained || $this === self::Decided;
    }
}
