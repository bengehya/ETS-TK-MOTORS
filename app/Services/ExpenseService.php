<?php

namespace App\Services;

use App\Enums\Currency;
use App\Enums\ExpenseStatus;
use App\Enums\Permission;
use App\Exceptions\InsufficientCashException;
use App\Exceptions\OperationAlreadyProcessedException;
use App\Models\Expense;
use App\Models\User;
use App\Support\Money;
use App\Support\ReferenceCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ExpenseService
{
    public function __construct(
        private readonly CashService $cash,
        private readonly AuditLogger $audit,
    ) {}

    public function create(User $user, string $amount, string $reason, string $spentOn, Currency $currency = Currency::Usd): Expense
    {
        $this->assertCanManage($user);

        $amount = Money::normalize($amount);
        $reason = trim($reason);

        if (Money::cmp($amount, '0.00') <= 0) {
            throw new InvalidArgumentException('Le montant de la dépense doit être positif.');
        }

        if ($reason === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour une dépense.');
        }

        $expense = Expense::query()->create([
            'organization_id' => $user->organization_id,
            'reference' => 'TMP-'.Str::uuid(),
            'amount' => $amount,
            'currency' => $currency,
            'reason' => $reason,
            'spent_on' => $spentOn,
            'status' => ExpenseStatus::Pending,
            'created_by' => $user->id,
        ]);

        $expense->reference = ReferenceCode::make('DEP', $expense->id);
        $expense->save();

        $this->audit->record($user, 'expense.created', $expense, null, [
            'reference' => $expense->reference,
            'amount' => $amount,
            'currency' => $currency->value,
            'reason' => $reason,
            'spent_on' => $spentOn,
            'status' => ExpenseStatus::Pending->value,
        ]);

        return $expense->fresh(['creator']);
    }

    public function validate(User $user, Expense $expense): Expense
    {
        $this->assertCanManage($user);
        $this->assertSameOrganization($user, $expense);

        return DB::transaction(function () use ($user, $expense): Expense {
            /** @var Expense $locked */
            $locked = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new OperationAlreadyProcessedException('Cette dépense a déjà été traitée.');
            }

            $amount = Money::normalize($locked->amount);
            $currency = $locked->currency instanceof Currency ? $locked->currency : Currency::Usd;

            try {
                $entry = $this->cash->outflow($user, $amount, $locked, 'Dépense '.$locked->reference, $currency);
                $locked->forceFill([
                    'status' => ExpenseStatus::Validated,
                    'cash_entry_id' => $entry->id,
                    'decision_note' => null,
                ]);
                $action = 'expense.validated';
            } catch (InsufficientCashException) {
                $locked->forceFill([
                    'status' => ExpenseStatus::Refused,
                    'decision_note' => 'Solde de caisse insuffisant. La dépense est refusée et la caisse n’est pas débitée.',
                ]);
                $action = 'expense.refused';
            }

            $locked->forceFill([
                'decided_by' => $user->id,
                'decided_at' => now(),
            ])->save();

            $this->audit->record($user, $action, $locked, [
                'status' => ExpenseStatus::Pending->value,
            ], [
                'status' => $locked->status->value,
                'reference' => $locked->reference,
                'amount' => $amount,
                'currency' => $currency->value,
                'cash_entry_id' => $locked->cash_entry_id,
            ], $locked->decision_note);

            return $locked->fresh(['creator', 'decider', 'cashEntry']);
        });
    }

    public function refuse(User $user, Expense $expense, string $note): Expense
    {
        $this->assertCanManage($user);
        $this->assertSameOrganization($user, $expense);

        $note = trim($note);

        if ($note === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour refuser une dépense.');
        }

        return DB::transaction(function () use ($user, $expense, $note): Expense {
            /** @var Expense $locked */
            $locked = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new OperationAlreadyProcessedException('Cette dépense a déjà été traitée.');
            }

            $locked->forceFill([
                'status' => ExpenseStatus::Refused,
                'decided_by' => $user->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ])->save();

            $this->audit->record($user, 'expense.refused', $locked, [
                'status' => ExpenseStatus::Pending->value,
            ], [
                'status' => ExpenseStatus::Refused->value,
                'reference' => $locked->reference,
                'amount' => Money::normalize($locked->amount),
            ], $note);

            return $locked->fresh(['creator', 'decider']);
        });
    }

    private function assertCanManage(User $user): void
    {
        if (! $user->hasPermission(Permission::ManageExpenses)) {
            throw new AuthorizationException('Action non autorisée.');
        }
    }

    private function assertSameOrganization(User $user, Expense $expense): void
    {
        if ($user->organization_id !== $expense->organization_id) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }
    }
}
