<?php

namespace App\Http\Controllers\Stock;

use App\Enums\LocationType;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\LocationProvisioner;
use App\Support\CatalogPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockLocationController extends Controller
{
    public function overview(Request $request, LocationProvisioner $locations): Response
    {
        $this->authorize('viewAny', Product::class);

        $organization = $request->user()->organization;
        $boutique = $locations->boutique($organization);
        $depot = $locations->depot($organization);

        $inventories = Inventory::query()
            ->where('organization_id', $organization->id)
            ->with(['product', 'location'])
            ->whereHas('product', function ($query) use ($request): void {
                $query->search($request->string('q')->toString())
                    ->when($request->input('status') === 'active', fn ($builder) => $builder->where('is_active', true))
                    ->when($request->input('status') === 'inactive', fn ($builder) => $builder->where('is_active', false));
            })
            ->get()
            ->groupBy('product_id');

        $rows = $inventories->map(function ($items) {
            /** @var Inventory $first */
            $first = $items->first();
            $product = $first->product;
            $product->setRelation('inventories', $items->values());

            return CatalogPresenter::product($product);
        })->sortBy('name')->values();

        return Inertia::render('Stock/Overview', [
            'products' => $rows,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'totals' => [
                'boutique' => Inventory::query()->where('location_id', $boutique->id)->sum('quantity'),
                'depot' => Inventory::query()->where('location_id', $depot->id)->sum('quantity'),
            ],
            'canMutateStock' => $request->user()->hasPermission(Permission::ManageProducts),
        ]);
    }

    public function boutique(Request $request, LocationProvisioner $locations): Response
    {
        return $this->locationView($request, $locations, LocationType::Boutique, 'Stock/Boutique');
    }

    public function depot(Request $request, LocationProvisioner $locations): Response
    {
        return $this->locationView($request, $locations, LocationType::Depot, 'Stock/Depot');
    }

    private function locationView(Request $request, LocationProvisioner $locations, LocationType $type, string $page): Response
    {
        $this->authorize('viewAny', Product::class);

        $organization = $request->user()->organization;
        $location = $type === LocationType::Boutique
            ? $locations->boutique($organization)
            : $locations->depot($organization);

        $query = Inventory::query()
            ->where('location_id', $location->id)
            ->with(['product', 'location'])
            ->whereHas('product', function ($builder) use ($request): void {
                $builder->search($request->string('q')->toString())
                    ->when($request->filled('category'), fn ($inner) => $inner->where('category', $request->string('category')->toString()))
                    ->when($request->input('status') === 'active', fn ($inner) => $inner->where('is_active', true))
                    ->when($request->input('status') === 'inactive', fn ($inner) => $inner->where('is_active', false));
            })
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->orderBy('products.name')
            ->select('inventories.*');

        $pageResults = $query->paginate(15)->withQueryString();

        $categories = Product::query()
            ->forOrganization($organization->id)
            ->orderBy('category')
            ->distinct()
            ->pluck('category')
            ->values();

        return Inertia::render($page, [
            'location' => CatalogPresenter::location($location),
            'rows' => [
                'data' => $pageResults->getCollection()->map(function (Inventory $inventory) {
                    return [
                        'inventory' => CatalogPresenter::inventory($inventory),
                        'product' => CatalogPresenter::product($inventory->product, false),
                    ];
                })->all(),
                'links' => $pageResults->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $pageResults->currentPage(),
                    'last_page' => $pageResults->lastPage(),
                    'total' => $pageResults->total(),
                    'from' => $pageResults->firstItem(),
                    'to' => $pageResults->lastItem(),
                ],
            ],
            'filters' => [
                'q' => $request->string('q')->toString(),
                'category' => $request->string('category')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'categories' => $categories,
            'canMutateStock' => $request->user()->hasPermission(Permission::ManageProducts),
            'totalQuantity' => Inventory::query()->where('location_id', $location->id)->sum('quantity'),
        ]);
    }
}
