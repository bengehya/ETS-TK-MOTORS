<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Support\Money;

class SavingsService
{
    /**
     * Suggestion d'épargne : 10 % de la moyenne journalière du chiffre d'affaires
     * des 30 derniers jours. Aucun mouvement de caisse n'est créé.
     *
     * @return array<string, mixed>
     */
    public function suggestion(int $organizationId): array
    {
        $revenue = Sale::query()
            ->where('organization_id', $organizationId)
            ->where('status', SaleStatus::Completed)
            ->where('sold_at', '>=', now()->subDays(30))
            ->sum('line_total');

        $normalized = Money::normalize($revenue);

        if (Money::cmp($normalized, '0.00') <= 0) {
            return [
                'available' => false,
                'automatic' => false,
                'period_days' => 30,
                'rate_percent' => 10,
                'revenue' => null,
                'daily_average' => null,
                'suggested_amount' => null,
                'message' => 'Aucun chiffre d’affaires sur les 30 derniers jours. Aucune épargne n’est suggérée.',
            ];
        }

        $average = Money::div($normalized, '30');
        $suggested = Money::percent($average, '10');

        return [
            'available' => true,
            'automatic' => false,
            'period_days' => 30,
            'rate_percent' => 10,
            'revenue' => $normalized,
            'daily_average' => $average,
            'suggested_amount' => $suggested,
            'message' => 'Épargne suggérée : '.$suggested,
        ];
    }
}
