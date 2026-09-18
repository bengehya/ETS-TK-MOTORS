<?php

namespace App\Http\Controllers\Catalog;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductRequest;
use App\Http\Requests\Catalog\UpdateProductRequest;
use App\Models\Product;
use App\Support\CatalogPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $organizationId = $request->user()->organization_id;

        $query = Product::query()
            ->forOrganization($organizationId)
            ->with(['inventories.location'])
            ->search($request->string('q')->toString())
            ->when($request->filled('category'), fn ($builder) => $builder->where('category', $request->string('category')->toString()))
            ->when($request->input('status') === 'active', fn ($builder) => $builder->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($builder) => $builder->where('is_active', false))
            ->orderBy('name');

        $products = $query->paginate(15)->withQueryString();

        $categories = Product::query()
            ->forOrganization($organizationId)
            ->orderBy('category')
            ->distinct()
            ->pluck('category')
            ->values();

        return Inertia::render('Catalog/Products/Index', [
            'products' => [
                'data' => $products->getCollection()->map(fn (Product $product) => CatalogPresenter::product($product))->all(),
                'links' => $products->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ],
            'filters' => [
                'q' => $request->string('q')->toString(),
                'category' => $request->string('category')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'categories' => $categories,
            'canManage' => $request->user()->can('create', Product::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Product::class);

        $categories = Product::query()
            ->forOrganization($request->user()->organization_id)
            ->orderBy('category')
            ->distinct()
            ->pluck('category')
            ->values();

        return Inertia::render('Catalog/Products/Create', [
            'categories' => $categories,
            'canUpdatePrice' => $request->user()->hasPermission(Permission::UpdatePrices),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            return Product::query()->create([
                ...$request->validated(),
                'organization_id' => $request->user()->organization_id,
                'created_by' => $request->user()->id,
                'is_active' => true,
            ]);
        });

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Article créé.');
    }

    public function show(Request $request, Product $product): Response
    {
        $this->authorize('view', $product);

        $product->load(['inventories.location']);

        return Inertia::render('Catalog/Products/Show', [
            'product' => CatalogPresenter::product($product),
            'canManage' => $request->user()->can('update', $product),
            'canMutateStock' => $request->user()->can('mutateStock', $product),
            'canUpdatePrice' => $request->user()->can('updatePrice', $product),
        ]);
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->authorize('update', $product);

        $product->load(['inventories.location']);

        $categories = Product::query()
            ->forOrganization($request->user()->organization_id)
            ->orderBy('category')
            ->distinct()
            ->pluck('category')
            ->values();

        return Inertia::render('Catalog/Products/Edit', [
            'product' => CatalogPresenter::product($product),
            'categories' => $categories,
            'canUpdatePrice' => $request->user()->can('updatePrice', $product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Article mis à jour.');
    }

    public function deactivate(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update(['is_active' => false]);

        return back()->with('status', 'Article désactivé. L’historique est conservé.');
    }

    public function activate(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update(['is_active' => true]);

        return back()->with('status', 'Article réactivé.');
    }
}
