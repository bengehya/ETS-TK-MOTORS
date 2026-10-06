<?php

namespace App\Services;

use App\Enums\CashDirection;
use App\Exceptions\InsufficientCashException;
use App\Models\CashAccount;
use App\Models\CashEntry;
use App\Models\User;
use App\Support\Money;
use App\Support\ReferenceCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CashService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function displayedBalance(int $organizationId): string
    {
        $balance = CashAccount::query()
            ->where('organization_id', $organizationId)
            ->value('balance');

        return Money::normalize($balance);
    }

    public function inflow(User $user, string $amount, Model $source, string $label): CashEntry
    {
        return $this->record($user, CashDirection::Inflow, $amount, $source, $label);
    }

    public function outflow(User $user, string $amount, Model $source, string $label): CashEntry
    {
        return $this->record($user, CashDirection::Outflow, $amount, $source, $label);
    }

    public function record(User $user, CashDirection $direction, string $amount, Model $source, string $label): CashEntry
    {
        $amount = Money::normalize($amount);

        if (Money::cmp($amount, '0.00') <= 0) {
            throw new InvalidArgumentException('Le montant de caisse doit être positif.');
        }

        return DB::transaction(function () use ($user, $direction, $amount, $source, $label): CashEntry {
            $account = $this->lockedAccount($user->organization_id);
            $before = Money::normalize($account->balance);

            if ($direction === CashDirection::Outflow && Money::cmp($before, $amount) < 0) {
                throw new InsufficientCashException('Le solde de caisse est insuffisant.');
            }

            $after = $direction === CashDirection::Inflow
                ? Money::add($before, $amount)
                : Money::sub($before, $amount);

            $account->balance = $after;
            $account->save();

            $entry = CashEntry::query()->create([
                'organization_id' => $user->organization_id,
                'cash_account_id' => $account->id,
                'reference' => 'TMP-'.Str::uuid(),
                'direction' => $direction,
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
                'amount' => $amount,
                'balance' => $after,
                'source_type' => $source::class,
                'source_id' => $source->getKey(),
            ]);

            return $entry->refresh();
        });
    }

    private function lockedAccount(int $organizationId): CashAccount
    {
        $account = CashAccount::query()
            ->where('organization_id', $organizationId)
            ->lockForUpdate()
            ->first();

        if ($account !== null) {
            return $account;
        }

        CashAccount::query()->create([
            'organization_id' => $organizationId,
            'balance' => '0.00',
        ]);

        return CashAccount::query()
            ->where('organization_id', $organizationId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
