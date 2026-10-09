<?php

namespace App\Enums;

enum AdjustmentMotif: string
{
    case Perte = 'perte';
    case Casse = 'casse';
    case ErreurComptage = 'erreur_comptage';
    case DifferenceInventaire = 'difference_inventaire';
    case PieceRetrouvee = 'piece_retrouvee';
    case ErreurSaisie = 'erreur_saisie';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Perte => 'Perte',
            self::Casse => 'Casse',
            self::ErreurComptage => 'Erreur de comptage',
            self::DifferenceInventaire => 'Différence d’inventaire',
            self::PieceRetrouvee => 'Pièce retrouvée',
            self::ErreurSaisie => 'Erreur de saisie',
            self::Autre => 'Autre correction exceptionnelle',
        };
    }
}
