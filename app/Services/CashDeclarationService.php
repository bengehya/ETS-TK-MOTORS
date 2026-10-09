<?php

namespace App\Services;

use App\Enums\CashDeclarationStatus;
use App\Enums\Currency;
use App\Enums\Permission;
use App\Models\CashDeclaration;
use App\Models\User;
use App\Support\Money;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashDeclarationService
{
    public function __construct(
        private readonly CashService $cash,
        private readonly ExchangeService $exchange,
        private readonly AuditLogger $audit,
    ) {}

    public function declare(
        User $user,
        ?string $note,
        ?string $countedUsd,
        ?string $countedCdf,
    ): CashDeclaration {
        $this->assertCanDeclare($user);

        return $this->store($user, $note, $countedUsd, $countedCdf, null, null);
    }

    public function correct(
        User $user,
        CashDeclaration $declaration,
        ?string $note,
        ?string $countedUsd,
        ?string $countedCdf,
        string $reason,
    ): CashDeclaration {
        $this->assertCanValidate($user);
        $this->assertSameOrganization($user, $declaration);

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour corriger une déclaration.');
        }

        return $this->store($user, $note, $countedUsd, $countedCdf, $declaration, $reason);
    }

    public function validate(User $user, CashDeclaration $declaration): CashDeclaration
    {
        $this->assertCanValidate($user);
        $this->assertSameOrganization($user, $declaration);

        if ($declaration->status === CashDeclarationStatus::Validated) {
            throw new InvalidArgumentException('Cette déclaration est déjà validée.');
        }

        $declaration->forceFill([
            'status' => CashDeclarationStatus::Validated,
            'validated_by' => $user->id,
            'validated_at' => now(),
        ])->save();

        $this->audit->record($user, 'cash.declaration.validated', $declaration, null, [
            'status' => CashDeclarationStatus::Validated->value,
        ]);

        return $declaration->fresh(['author', 'validator']);
    }

    private function store(
        User $user,
        ?string $note,
        ?string $countedUsd,
        ?string $countedCdf,
        ?CashDeclaration $previous,
        ?string $correctionReason,
    ): CashDeclaration {
        $note = $this->blankToNull($note);
        $countedUsd = $this->optionalMoney($countedUsd);
        $countedCdf = $this->optionalMoney($countedCdf);

        if ($note === null && $countedUsd === null && $countedCdf === null) {
            throw new InvalidArgumentException('Une note ou un comptage est obligatoire.');
        }

        return DB::transaction(function () use ($user, $note, $countedUsd, $countedCdf, $previous, $correctionReason): CashDeclaration {
            $bookUsd = $this->cash->displayedBalance($user->organization_id, Currency::Usd);
            $bookCdf = $this->cash->displayedBalance($user->organization_id, Currency::Cdf);
            $rate = $this->exchange->currentRate($user->organization_id);
            $rateValue = $rate ? Money::normalizeRate($rate->cdf_per_usd) : null;

            $declaration = CashDeclaration::query()->create([
                'organization_id' => $user->organization_id,
                'user_id' => $user->id,
                'previous_declaration_id' => $previous?->id,
                'note' => $note,
                'counted_usd' => $countedUsd,
                'counted_cdf' => $countedCdf,
                'book_usd' => $bookUsd,
                'book_cdf' => $bookCdf,
                'gap_usd' => $countedUsd === null ? null : Money::sub($countedUsd, $bookUsd),
                'gap_cdf' => $countedCdf === null ? null : Money::sub($countedCdf, $bookCdf),
                'reference_rate' => $rateValue,
                'rate_effective_at' => $rate?->effective_at,
                'indicative_usd' => $this->indicativeUsd($countedUsd, $countedCdf, $rateValue),
                'status' => $previous === null ? CashDeclarationStatus::Declared : CashDeclarationStatus::Corrected,
                'correction_reason' => $correctionReason,
            ]);

            $this->audit->record($user, $previous === null ? 'cash.declaration.created' : 'cash.declaration.corrected', $declaration, null, [
                'counted_usd' => $countedUsd,
                'counted_cdf' => $countedCdf,
                'book_usd' => $bookUsd,
                'book_cdf' => $bookCdf,
                'gap_usd' => $declaration->gap_usd,
                'gap_cdf' => $declaration->gap_cdf,
                'reference_rate' => $rateValue,
                'previous_declaration_id' => $previous?->id,
            ], $correctionReason ?? $note);

            return $declaration->fresh(['author']);
        });
    }

    private function indicativeUsd(?string $countedUsd, ?string $countedCdf, ?string $rate): ?string
    {
        if ($rate === null || bccomp($rate, '0', 4) <= 0) {
            return null;
        }

        $usd = $countedUsd ?? '0.00';
        $cdfPart = $countedCdf === null ? '0.00' : Money::divRate($countedCdf, $rate);

        if ($countedUsd === null && $countedCdf === null) {
            return null;
        }

        return Money::add($usd, $cdfPart);
    }

    private function optionalMoney(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = Money::normalize($value);

        if (Money::cmp($normalized, '0.00') < 0) {
            throw new InvalidArgumentException('Un comptage ne peut pas être négatif.');
        }

        return $normalized;
    }

    private function assertCanDeclare(User $user): void
    {
        if (! $user->hasPermission(Permission::CreateSales) && ! $user->hasPermission(Permission::ManageExpenses)) {
            throw new AuthorizationException('Action non autorisée.');
        }
    }

    private function assertCanValidate(User $user): void
    {
        if (! $user->hasPermission(Permission::ManageExpenses)) {
            throw new AuthorizationException('Action non autorisée.');
        }
    }

    private function assertSameOrganization(User $user, CashDeclaration $declaration): void
    {
        if ($user->organization_id !== $declaration->organization_id) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
