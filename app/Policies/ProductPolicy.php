<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::SearchProducts);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermission(Permission::SearchProducts)
            && $user->organization_id === $product->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageProducts);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission(Permission::ManageProducts)
            && $user->organization_id === $product->organization_id;
    }

    public function updatePrice(User $user, Product $product): bool
    {
        return $user->hasPermission(Permission::UpdatePrices)
            && $user->organization_id === $product->organization_id;
    }

    public function mutateStock(User $user, Product $product): bool
    {
        return $user->hasPermission(Permission::ManageProducts)
            && $user->organization_id === $product->organization_id;
    }
}
