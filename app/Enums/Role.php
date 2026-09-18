<?php

namespace App\Enums;

enum Role: string
{
    case BossPrincipal = 'BOSS_PRINCIPAL';
    case BossSecondaire = 'BOSS_SECONDAIRE';
    case Employe = 'EMPLOYE';

    public function label(): string
    {
        return match ($this) {
            self::BossPrincipal => 'Patron principal',
            self::BossSecondaire => 'Patron secondaire',
            self::Employe => 'Employé',
        };
    }

    public function isBoss(): bool
    {
        return $this === self::BossPrincipal || $this === self::BossSecondaire;
    }
}
