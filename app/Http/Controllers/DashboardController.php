<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\LocationProvisioner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LocationProvisioner $locations): Response
    {
        $user = $request->user();
        $organization = $user->organization;
        $boutique = $locations->boutique($organization);
        $depot = $locations->depot($organization);

        return Inertia::render('Dashboard', [
            'catalog' => [
                'active_products' => Product::query()
                    ->forOrganization($organization->id)
                    ->where('is_active', true)
                    ->count(),
                'boutique_quantity' => (int) Inventory::query()->where('location_id', $boutique->id)->sum('quantity'),
                'depot_quantity' => (int) Inventory::query()->where('location_id', $depot->id)->sum('quantity'),
            ],
            'canViewCatalog' => $user->hasPermission(Permission::SearchProducts),
        ]);
    }
}
