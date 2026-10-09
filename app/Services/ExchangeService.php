<?php

namespace App\Services;

use App\Enums\Currency;
use App\Enums\Permission;
use App\Exceptions\InsufficientCashException;
use App\Exceptions\MissingExchangeRateException;
use App\Models\Exchange;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Support\Money;
use App\Support\ReferenceCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ExchangeService
{
    public function __construct(
        private readonly CashService $cash,
        private readonly AuditLogger $audit,
    ) {}

    public function currentRate(int $organizationId): ?ExchangeRate
    {
        return ExchangeRate::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('effective_at')
            ->orderByDesc('id')
            ->first();
    }

    public function setRate(User $user, string $cdfPerUsd): ExchangeRate
    {
        $this->assertCanManage($user);
        $rate = Money::normalizeRate($cdfPerUsd);

        if (bccomp($rate, '0', 4) <= 0) {
            throw new InvalidArgumentException('Le taux doit être supérieur à zéro.');
        }

        $record = ExchangeRate::query()->create([
            'organization_id' => $user->organization_id,
            'cdf_per_usd' => $rate,
            'effective_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->audit->record($user, 'exchange.rate_set', $record, null, [
            'cdf_per_usd' => $rate,
            'effective_at' => $record->effective_at?->toIso8601String(),
        ]);

        return $record->fresh('creator');
    }

    /**
     * Aperçu calculé côté serveur. Aucune écriture n'est créée.
     *
     * @return array<string, mixed>
     */
    public function preview(int $organizationId, Currency $source, string $amount): array
    {
        $rate = $this->currentRate($organizationId);
        $normalized = Money::normalize($amount);

        if ($rate === null) {
            return [
                'available' => false,
                'message' => 'Aucun taux n’est défini.',
            ];
        }

        $destination = $this->destinationAmount($source, $normalized, (string) $rate->cdf_per_usd);

        return [
            'available' => true,
            'source_currency' => $source->value,
            'source_amount' => $normalized,
            'destination_currency' => $source->opposite()->value,
            'destination_amount' => $destination,
            'rate' => Money::normalizeRate($rate->cdf_per_usd),
            'effective_at' => $rate->effective_at?->timezone(config('app.timezone'))->toIso8601String(),
            'effective_at_label' => $rate->effective_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ];
    }

    public function convert(User $user, Currency $source, string $amount): Exchange
    {
        $this->assertCanManage($user);
        $amount = Money::normalize($amount);

        if (Money::cmp($amount, '0.00') <= 0) {
            throw new InvalidArgumentException('Le montant à convertir doit être positif.');
        }

        $rate = $this->currentRate($user->organization_id);

        if ($rate === null) {
            throw new MissingExchangeRateException('Aucun taux n’est défini.');
        }

        $appliedRate = Money::normalizeRate($rate->cdf_per_usd);
        $destinationAmount = $this->destinationAmount($source, $amount, $appliedRate);

        if (Money::cmp($destinationAmount, '0.00') <= 0) {
            throw new InvalidArgumentException('Le montant converti est nul. L’opération est refusée.');
        }

        $destination = $source->opposite();

        return DB::transaction(function () use ($user, $source, $amount, $destination, $destinationAmount, $appliedRate, $rate): Exchange {
            $exchange = Exchange::query()->create([
                'organization_id' => $user->organization_id,
                'reference' => 'TMP-'.Str::uuid(),
                'source_currency' => $source,
                'source_amount' => $amount,
                'destination_currency' => $destination,
                'destination_amount' => $destinationAmount,
                'rate' => $appliedRate,
                'exchange_rate_id' => $rate->id,
                'created_by' => $user->id,
                'occurred_at' => now(),
            ]);

            $exchange->reference = ReferenceCode::make('CHG', $exchange->id);
            $exchange->save();

            try {
                [$outflow, $inflow] = $this->cash->movePair(
                    $user,
                    $source,
                    $amount,
                    $destination,
                    $destinationAmount,
                    $exchange,
                    'Change '.$exchange->reference,
                    'Change '.$exchange->reference,
                );
            } catch (InsufficientCashException) {
                throw new InsufficientCashException('Le solde de la devise source est insuffisant.');
            }

            $exchange->forceFill([
                'source_entry_id' => $outflow->id,
                'destination_entry_id' => $inflow->id,
            ])->save();

            $this->audit->record($user, 'exchange.converted', $exchange, null, [
                'reference' => $exchange->reference,
                'source_currency' => $source->value,
                'source_amount' => $amount,
                'destination_currency' => $destination->value,
                'destination_amount' => $destinationAmount,
                'rate' => $appliedRate,
                'exchange_rate_id' => $rate->id,
            ]);

            return $exchange->fresh(['creator', 'exchangeRate']);
        });
    }

    private function destinationAmount(Currency $source, string $amount, string $cdfPerUsd): string
    {
        return $source === Currency::Usd
            ? Money::mulRate($amount, $cdfPerUsd)
            : Money::divRate($amount, $cdfPerUsd);
    }

    private function assertCanManage(User $user): void
    {
        if (! $user->hasPermission(Permission::ManageExpenses)) {
            throw new AuthorizationException('Action non autorisée.');
        }
    }
}
