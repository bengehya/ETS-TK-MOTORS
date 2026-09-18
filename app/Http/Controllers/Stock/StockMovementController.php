<?php

namespace App\Http\Controllers\Stock;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\LocationProvisioner;
use App\Support\CatalogPresenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $organization = $request->user()->organization;
        app(LocationProvisioner::class)->provision($organization);

        $request->merge([
            'q' => $request->filled('q') ? $request->string('q')->toString() : null,
            'type' => $request->filled('type') ? $request->string('type')->toString() : null,
            'location_id' => $request->filled('location_id') ? $request->integer('location_id') : null,
        ]);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(StockMovementType::class)],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('organization_id', $organization->id)],
        ]);

        $movements = StockMovement::query()
            ->where('organization_id', $organization->id)
            ->with(['product', 'location', 'user'])
            ->when(filled($validated['q'] ?? null), function ($query) use ($validated): void {
                $query->whereHas('product', fn ($product) => $product->search($validated['q']));
            })
            ->when(filled($validated['type'] ?? null), fn ($query) => $query->where('type', $validated['type']))
            ->when(filled($validated['location_id'] ?? null), fn ($query) => $query->where('location_id', $validated['location_id']))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Stock/Movements', [
            'movements' => [
                'data' => $movements->getCollection()->map(fn (StockMovement $movement) => CatalogPresenter::movement($movement))->all(),
                'links' => $movements->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $movements->currentPage(),
                    'last_page' => $movements->lastPage(),
                    'total' => $movements->total(),
                    'from' => $movements->firstItem(),
                    'to' => $movements->lastItem(),
                ],
            ],
            'filters' => [
                'q' => $validated['q'] ?? '',
                'type' => $validated['type'] ?? '',
                'location_id' => $validated['location_id'] ?? null,
            ],
            'types' => collect(StockMovementType::cases())->map(fn (StockMovementType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'locations' => $organization->locations()->orderBy('name')->get()->map(
                fn ($location) => CatalogPresenter::location($location)
            )->values(),
        ]);
    }
}
