<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Arrival;
use App\Models\User;

class ArrivalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::RecordStockReceipts)
            || $user->hasPermission(Permission::ValidateStockReceipts);
    }

    public function view(User $user, Arrival $arrival): bool
    {
        if ($user->organization_id !== $arrival->organization_id) {
            return false;
        }

        if ($user->hasPermission(Permission::ValidateStockReceipts)) {
            return true;
        }

        return $user->hasPermission(Permission::RecordStockReceipts)
            && $arrival->recorded_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::RecordStockReceipts);
    }

    public function approve(User $user, Arrival $arrival): bool
    {
        return $user->hasPermission(Permission::ValidateStockReceipts)
            && $user->organization_id === $arrival->organization_id;
    }

    public function reject(User $user, Arrival $arrival): bool
    {
        return $this->approve($user, $arrival);
    }
}
