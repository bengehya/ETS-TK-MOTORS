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
use RuntimeException;

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
        $before = $this->cash->balances($organizationId);

        try {
            $normalized = $this->normalizeAmount($amount);
        } catch (InvalidArgumentException $exception) {
            return [
                'available' => false,
                'message' => $exception->getMessage(),
                'balances_before' => $before,
            ];
        }

        $rate = $this->currentRate($organizationId);

        if ($rate === null) {
            return [
                'available' => false,
                'message' => 'Aucun taux n’est défini.',
                'balances_before' => $before,
            ];
        }

        $appliedRate = Money::normalizeRate($rate->cdf_per_usd);
        $destinationAmount = $this->destinationAmount($source, $normalized, $appliedRate);
        $destination = $source->opposite();

        if (Money::cmp($destinationAmount, '0.00') <= 0) {
            return [
                'available' => false,
                'message' => 'Le montant converti est nul. L’opération est refusée.',
                'balances_before' => $before,
            ];
        }

        if (Money::cmp($before[$source->value], $normalized) < 0) {
            return [
                'available' => false,
                'message' => $this->insufficientMessage($source),
                'balances_before' => $before,
            ];
        }

        $after = $before;
        $after[$source->value] = Money::sub($before[$source->value], $normalized);
        $after[$destination->value] = Money::add($before[$destination->value], $destinationAmount);

        return [
            'available' => true,
            'source_currency' => $source->value,
            'source_amount' => $normalized,
            'destination_currency' => $destination->value,
            'destination_amount' => $destinationAmount,
            'fee_amount' => '0.00',
            'rate' => $appliedRate,
            'effective_at' => $rate->effective_at?->timezone(config('app.timezone'))->toIso8601String(),
            'effective_at_label' => $rate->effective_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'balances_before' => $before,
            'balances_after' => $after,
        ];
    }

    public function convert(User $user, Currency $source, string $amount): Exchange
    {
        $this->assertCanManage($user);

        try {
            $amount = $this->normalizeAmount($amount);
        } catch (InvalidArgumentException $exception) {
            $this->refuse($user, $source, $amount, $exception->getMessage());
        }

        $rate = $this->currentRate($user->organization_id);

        if ($rate === null) {
            $this->refuse($user, $source, $amount, 'Aucun taux n’est défini.', MissingExchangeRateException::class);
        }

        $appliedRate = Money::normalizeRate($rate->cdf_per_usd);
        $destinationAmount = $this->destinationAmount($source, $amount, $appliedRate);

        if (Money::cmp($destinationAmount, '0.00') <= 0) {
            $this->refuse($user, $source, $amount, 'Le montant converti est nul. L’opération est refusée.');
        }

        $destination = $source->opposite();

        try {
            return DB::transaction(function () use ($user, $source, $amount, $destination, $destinationAmount, $appliedRate, $rate): Exchange {
                $exchange = Exchange::query()->create([
                    'organization_id' => $user->organization_id,
                    'reference' => 'TMP-'.Str::uuid(),
                    'source_currency' => $source,
                    'source_amount' => $amount,
                    'destination_currency' => $destination,
                    'destination_amount' => $destinationAmount,
                    'rate' => $appliedRate,
                    'fee_amount' => '0.00',
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
                    throw new InsufficientCashException($this->insufficientMessage($source));
                }

                $exchange->forceFill([
                    'source_entry_id' => $outflow->id,
                    'destination_entry_id' => $inflow->id,
                ])->save();

                $this->audit->record($user, 'exchange.converted', $exchange, [
                    'source_balance' => Money::normalize($outflow->balance_before),
                    'destination_balance' => Money::normalize($inflow->balance_before),
                ], [
                    'reference' => $exchange->reference,
                    'source_currency' => $source->value,
                    'source_amount' => $amount,
                    'destination_currency' => $destination->value,
                    'destination_amount' => $destinationAmount,
                    'rate' => $appliedRate,
                    'fee_amount' => '0.00',
                    'exchange_rate_id' => $rate->id,
                    'source_balance' => Money::normalize($outflow->balance_after),
                    'destination_balance' => Money::normalize($inflow->balance_after),
                ]);

                return $exchange->fresh(['creator', 'exchangeRate']);
            });
        } catch (InsufficientCashException $exception) {
            $this->audit->safeRecord($user, 'exchange.refused', null, null, [
                'source_currency' => $source->value,
                'source_amount' => $amount,
                'destination_currency' => $destination->value,
                'destination_amount' => $destinationAmount,
                'rate' => $appliedRate,
                'fee_amount' => '0.00',
            ], $exception->getMessage(), 'failure');

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function settlement(Exchange $exchange): array
    {
        $exchange->loadMissing(['sourceEntry', 'destinationEntry']);
        $before = [
            Currency::Usd->value => '0.00',
            Currency::Cdf->value => '0.00',
        ];
        $after = $before;

        if ($exchange->sourceEntry !== null) {
            $code = $exchange->source_currency->value;
            $before[$code] = Money::normalize($exchange->sourceEntry->balance_before);
            $after[$code] = Money::normalize($exchange->sourceEntry->balance_after);
        }

        if ($exchange->destinationEntry !== null) {
            $code = $exchange->destination_currency->value;
            $before[$code] = Money::normalize($exchange->destinationEntry->balance_before);
            $after[$code] = Money::normalize($exchange->destinationEntry->balance_after);
        }

        return [
            'reference' => $exchange->reference,
            'source_currency' => $exchange->source_currency->value,
            'source_amount' => Money::normalize($exchange->source_amount),
            'destination_currency' => $exchange->destination_currency->value,
            'destination_amount' => Money::normalize($exchange->destination_amount),
            'rate' => Money::normalizeRate($exchange->rate),
            'fee_amount' => Money::normalize($exchange->fee_amount),
            'before' => $before,
            'after' => $after,
        ];
    }

    private function destinationAmount(Currency $source, string $amount, string $cdfPerUsd): string
    {
        return $source === Currency::Usd
            ? Money::mulRate($amount, $cdfPerUsd)
            : Money::divRate($amount, $cdfPerUsd);
    }

    private function normalizeAmount(string $amount): string
    {
        $amount = str_replace([' ', ','], ['', '.'], trim($amount));

        if (preg_match('/^\d+(\.\d{1,2})?$/', $amount) !== 1) {
            throw new InvalidArgumentException('Le montant à convertir doit être positif.');
        }

        $normalized = Money::normalize($amount);

        if (Money::cmp($normalized, '0.00') <= 0) {
            throw new InvalidArgumentException('Le montant à convertir doit être positif.');
        }

        return $normalized;
    }

    private function insufficientMessage(Currency $source): string
    {
        return 'Solde '.$source->value.' insuffisant.';
    }

    /**
     * @param  class-string<RuntimeException>  $exception
     */
    private function refuse(User $user, Currency $source, string $amount, string $message, string $exception = InvalidArgumentException::class): never
    {
        $this->audit->safeRecord($user, 'exchange.refused', null, null, [
            'source_currency' => $source->value,
            'source_amount' => $amount,
            'fee_amount' => '0.00',
        ], $message, 'failure');

        throw new $exception($message);
    }

    private function assertCanManage(User $user): void
    {
        if (! $user->hasPermission(Permission::ManageExpenses)) {
            $this->audit->safeRecord($user, 'permission.denied', null, null, [
                'permission' => Permission::ManageExpenses->value,
            ], 'Action non autorisée.', 'denied');

            throw new AuthorizationException('Action non autorisée.');
        }
    }
}
