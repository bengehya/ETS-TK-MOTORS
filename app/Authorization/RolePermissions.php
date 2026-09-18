<?php

namespace App\Authorization;

use App\Enums\Permission;
use App\Enums\Role;

final class RolePermissions
{
    /**
     * @return list<Permission>
     */
    public static function for(Role $role): array
    {
        $employeePermissions = [
            Permission::SearchProducts,
            Permission::CreateSales,
            Permission::ScanProducts,
            Permission::RecordStockReceipts,
            Permission::CreateCustomerRequests,
        ];

        $sharedBossPermissions = [
            Permission::ManageEmployees,
            Permission::ManageProducts,
            Permission::UpdatePrices,
            Permission::ValidateStockReceipts,
            Permission::ManageSales,
            Permission::CreateSales,
            Permission::CancelSales,
            Permission::ManageExpenses,
            Permission::ViewReports,
            Permission::ViewAudit,
            Permission::SearchProducts,
            Permission::ScanProducts,
            Permission::RecordStockReceipts,
            Permission::CreateCustomerRequests,
        ];

        return match ($role) {
            Role::BossPrincipal => [
                Permission::InviteBoss,
                ...$sharedBossPermissions,
            ],
            Role::BossSecondaire => $sharedBossPermissions,
            Role::Employe => $employeePermissions,
        };
    }

    public static function allows(Role $role, Permission $permission): bool
    {
        foreach (self::for($role) as $allowed) {
            if ($allowed === $permission) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function valuesFor(Role $role): array
    {
        return array_map(
            static fn (Permission $permission): string => $permission->value,
            self::for($role),
        );
    }
}
