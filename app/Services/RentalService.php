<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\RentalStatus;
use App\Exceptions\OperationAlreadyProcessedException;
use App\Models\Rental;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RentalService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function open(User $user, string $label, string $amount, string $startedOn, int $durationMonths): Rental
    {
        if (! $user->hasPermission(Permission::ManageRentals)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        $label = trim($label);
        $amount = Money::normalize($amount);

        if ($label === '') {
            throw new InvalidArgumentException('Le libellé de la location est obligatoire.');
        }

        if (Money::cmp($amount, '0.00') <= 0) {
            throw new InvalidArgumentException('Le montant de la location doit être positif.');
        }

        if ($durationMonths < 1) {
            throw new InvalidArgumentException('La durée doit être d’au moins un mois.');
        }

        $start = CarbonImmutable::parse($startedOn)->startOfDay();
        $end = $start->addMonths($durationMonths);

        $rental = Rental::query()->create([
            'organization_id' => $user->organization_id,
            'label' => $label,
            'amount' => $amount,
            'started_on' => $start->toDateString(),
            'duration_months' => $durationMonths,
            'ends_on' => $end->toDateString(),
            'status' => RentalStatus::Active,
            'recorded_by' => $user->id,
        ]);

        $this->audit->record($user, 'rental.created', $rental, null, [
            'label' => $label,
            'amount' => $amount,
            'started_on' => $start->toDateString(),
            'duration_months' => $durationMonths,
            'ends_on' => $end->toDateString(),
        ]);

        return $rental->fresh(['recorder']);
    }

    public function close(User $user, Rental $rental, string $reason): Rental
    {
        if (! $user->hasPermission(Permission::ManageRentals)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        if ($user->organization_id !== $rental->organization_id) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour clôturer une location.');
        }

        return DB::transaction(function () use ($user, $rental, $reason): Rental {
            /** @var Rental $locked */
            $locked = Rental::query()->whereKey($rental->id)->lockForUpdate()->firstOrFail();

            if ($locked->isClosed()) {
                throw new OperationAlreadyProcessedException('Cette location est déjà clôturée.');
            }

            $previous = $locked->status->value;

            $locked->forceFill([
                'status' => RentalStatus::Closed,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_reason' => $reason,
            ])->save();

            $this->audit->record($user, 'rental.closed', $locked, [
                'status' => $previous,
            ], [
                'status' => RentalStatus::Closed->value,
                'label' => $locked->label,
            ], $reason);

            return $locked->fresh(['recorder', 'closer']);
        });
    }

    public function syncExpired(User $user): void
    {
        $due = Rental::query()
            ->forOrganization($user->organization_id)
            ->where('status', RentalStatus::Active)
            ->whereDate('ends_on', '<', now()->toDateString())
            ->get();

        foreach ($due as $rental) {
            $rental->forceFill(['status' => RentalStatus::Expired])->save();

            $this->audit->record($user, 'rental.expired', $rental, [
                'status' => RentalStatus::Active->value,
            ], [
                'status' => RentalStatus::Expired->value,
                'ends_on' => $rental->ends_on?->toDateString(),
                'label' => $rental->label,
            ], 'Échéance atteinte');
        }
    }
}
