<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    public function __construct(private AuditLogger $audit) {}

    public function isEnabled(int $organizationId): bool
    {
        return (bool) Organization::query()->whereKey($organizationId)->value('maintenance_enabled');
    }

    public function enable(User $actor): void
    {
        $this->assertCanOperate($actor);

        $organization = $this->organization($actor);

        if ($organization->maintenance_enabled) {
            return;
        }

        DB::transaction(function () use ($actor, $organization): void {
            $organization->forceFill([
                'maintenance_enabled' => true,
                'maintenance_started_at' => now(),
                'maintenance_started_by' => $actor->id,
            ])->save();

            $this->audit->record(
                $actor,
                'maintenance.enabled',
                $organization,
                ['maintenance_enabled' => false],
                ['maintenance_enabled' => true],
                null,
                'success',
            );
        });
    }

    public function disable(User $actor): void
    {
        $this->assertCanOperate($actor);

        $organization = $this->organization($actor);

        if (! $organization->maintenance_enabled) {
            return;
        }

        DB::transaction(function () use ($actor, $organization): void {
            $organization->forceFill([
                'maintenance_enabled' => false,
                'maintenance_started_at' => null,
                'maintenance_started_by' => null,
            ])->save();

            $this->audit->record(
                $actor,
                'maintenance.disabled',
                $organization,
                ['maintenance_enabled' => true],
                ['maintenance_enabled' => false],
                null,
                'success',
            );
        });
    }

    private function organization(User $actor): Organization
    {
        $organization = $actor->organization;

        if ($organization === null) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return $organization;
    }

    private function assertCanOperate(User $actor): void
    {
        if ($actor->isBoss() && $actor->hasPermission(Permission::ManageEmployees)) {
            return;
        }

        $this->audit->safeRecord(
            $actor,
            'maintenance.refused',
            $actor->organization,
            null,
            null,
            'Action non autorisée.',
            'denied',
        );

        throw new AuthorizationException('Action non autorisée.');
    }
}
