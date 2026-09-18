<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Receipt = 'receipt';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Sale = 'sale';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Entrée de stock',
            self::TransferOut => 'Transfert sortant',
            self::TransferIn => 'Transfert entrant',
            self::Sale => 'Sortie vente',
            self::Adjustment => 'Ajustement',
        };
    }
}
