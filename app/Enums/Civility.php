<?php

namespace App\Enums;

enum Civility: string
{
    case Monsieur = 'monsieur';
    case Madame = 'madame';

    public function label(): string
    {
        return match ($this) {
            self::Monsieur => 'Monsieur',
            self::Madame => 'Madame',
        };
    }
}
