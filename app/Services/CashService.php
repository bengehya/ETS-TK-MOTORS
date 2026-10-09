<?php

namespace App\Services;

use App\Enums\CashDirection;
use App\Enums\Currency;
use App\Enums\Permission;
use App\Exceptions\InsufficientCashException;
use App\Models\CashAccount;
use App\Models\CashAdjustment;
use App\Models\CashEntry;
use App\Models\User;
use App\Support\Money;
use App\Support\ReferenceCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CashService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function displayedBalance(int $organizationId, Currency $currency = Currency::Usd): string
    {
        $balance = CashAccount::query()
            ->where('organization_id', $organizationId)
            ->where('currency', $currency->value)
            ->value('balance');

        return Money::normalize($balance);
    }

    /**
     * @return array{USD: string, CDF: string}
     */
    public function balances(int $organizationId): array
    {
        return [
            Currency::Usd->value => $this->displayedBalance($organizationId, Currency::Usd),
            Currency::Cdf->value => $this->displayedBalance($organizationId, Currency::Cdf),
        ];
    }

    public function inflow(User $user, string $amount, Model $source, string $label, Currency $currency = Currency::Usd): CashEntry
    {
        return $this->record($user, CashDirection::Inflow, $amount, $source, $label, $currency);
    }

    public function outflow(User $user, string $amount, Model $source, string $label, Currency $currency = Currency::Usd): CashEntry
    {
        return $this->record($user, CashDirection::Outflow, $amount, $source, $label, $currency);
    }

    public function record(
        User $user,
        CashDirection $direction,
        string $amount,
        Model $source,
        string $label,
        Currency $currency = Currency::Usd,
    ): CashEntry {
        $amount = $this->positiveAmount($amount);

        return DB::transaction(function () use ($user, $direction, $amount, $source, $label, $currency): CashEntry {
            $account = $this->lockedAccount($user->organization_id, $currency);

            return $this->postOnAccount($user, $account, $direction, $amount, $source, $label);
        });
    }

    /**
     * Sortie puis entrée, dans une seule transaction. Les comptes sont verrouillés
     * par code de devise pour éviter un interblocage.
     *
     * @return array{0: CashEntry, 1: CashEntry}
     */
    public function movePair(
        User $user,
        Currency $from,
        string $fromAmount,
        Currency $to,
        string $toAmount,
        Model $source,
        string $outLabel,
        string $inLabel,
    ): array {
        if ($from === $to) {
            throw new InvalidArgumentException('La conversion doit relier deux devises différentes.');
        }

        $fromAmount = $this->positiveAmount($fromAmount);
        $toAmount = $this->positiveAmount($toAmount);

        return DB::transaction(function () use ($user, $from, $fromAmount, $to, $toAmount, $source, $outLabel, $inLabel): array {
            $ordered = [$from, $to];
            usort($ordered, fn (Currency $left, Currency $right): int => $left->value <=> $right->value);

            $accounts = [];
            foreach ($ordered as $currency) {
                $accounts[$currency->value] = $this->lockedAccount($user->organization_id, $currency);
            }

            $outflow = $this->postOnAccount($user, $accounts[$from->value], CashDirection::Outflow, $fromAmount, $source, $outLabel);
            $inflow = $this->postOnAccount($user, $accounts[$to->value], CashDirection::Inflow, $toAmount, $source, $inLabel);

            return [$outflow, $inflow];
        });
    }

    public function adjust(User $user, Currency $currency, CashDirection $direction, string $amount, string $reason): CashAdjustment
    {
        if (! $user->hasPermission(Permission::ManageExpenses)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour corriger la caisse.');
        }

        $amount = $this->positiveAmount($amount);

        return DB::transaction(function () use ($user, $currency, $direction, $amount, $reason): CashAdjustment {
            $adjustment = CashAdjustment::query()->create([
                'organization_id' => $user->organization_id,
                'currency' => $currency,
                'direction' => $direction,
                'amount' => $amount,
                'reason' => $reason,
                'user_id' => $user->id,
            ]);

            $entry = $direction === CashDirection::Inflow
                ? $this->inflow($user, $amount, $adjustment, 'Correction de caisse', $currency)
                : $this->outflow($user, $amount, $adjustment, 'Correction de caisse', $currency);

            $adjustment->cash_entry_id = $entry->id;
            $adjustment->save();

            $this->audit->record($user, 'cash.adjusted', $adjustment, null, [
                'currency' => $currency->value,
                'direction' => $direction->value,
                'amount' => $amount,
                'cash_entry_id' => $entry->id,
            ], $reason);

            return $adjustment->fresh(['cashEntry']);
        });
    }

    private function positiveAmount(string $amount): string
    {
        $amount = Money::normalize($amount);

        if (Money::cmp($amount, '0.00') <= 0) {
            throw new InvalidArgumentException('Le montant de caisse doit être positif.');
        }

        return $amount;
    }

    private function postOnAccount(
        User $user,
        CashAccount $account,
        CashDirection $direction,
        string $amount,
        Model $source,
        string $label,
    ): CashEntry {
        $before = Money::normalize($account->balance);

        if ($direction === CashDirection::Outflow && Money::cmp($before, $amount) < 0) {
            throw new InsufficientCashException('Le solde de caisse est insuffisant.');
        }

        $after = $direction === CashDirection::Inflow
            ? Money::add($before, $amount)
            : Money::sub($before, $amount);

        $account->balance = $after;
        $account->save();

        $currency = $account->currency instanceof Currency
            ? $account->currency
            : Currency::from((string) $account->currency);

        $entry = CashEntry::query()->create([
            'organization_id' => $user->organization_id,
            'cash_account_id' => $account->id,
            'reference' => 'TMP-'.Str::uuid(),
            'direction' => $direction,
            'currency' => $currency,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'label' => $label,
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'user_id' => $user->id,
            'occurred_at' => now(),
        ]);

        $entry->reference = ReferenceCode::make('CAI', $entry->id);
        $entry->save();

        $this->audit->record($user, 'cash.movement', $entry, [
            'balance' => $before,
        ], [
            'reference' => $entry->reference,
            'direction' => $direction->value,
            'currency' => $currency->value,
            'amount' => $amount,
            'balance' => $after,
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
        ]);

        return $entry->refresh();
    }

    private function lockedAccount(int $organizationId, Currency $currency): CashAccount
    {
        $account = CashAccount::query()
            ->where('organization_id', $organizationId)
            ->where('currency', $currency->value)
            ->lockForUpdate()
            ->first();

        if ($account !== null) {
            return $account;
        }

        CashAccount::query()->create([
            'organization_id' => $organizationId,
            'currency' => $currency,
            'balance' => '0.00',
        ]);

        return CashAccount::query()
            ->where('organization_id', $organizationId)
            ->where('currency', $currency->value)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
