<?php

namespace App\Http\Controllers\Supply;

use App\Enums\ArrivalStatus;
use App\Enums\Permission;
use App\Exceptions\ArrivalAlreadyProcessedException;
use App\Exceptions\InactiveProductException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApproveArrivalRequest;
use App\Http\Requests\Supply\RejectArrivalRequest;
use App\Http\Requests\Supply\StoreArrivalRequest;
use App\Models\Arrival;
use App\Models\Product;
use App\Services\ArrivalService;
use App\Services\LocationProvisioner;
use App\Support\ArrivalPresenter;
use App\Support\CatalogPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ArrivalController extends Controller
{
    public function index(Request $request): Response
    {
        $request->merge([
            'q' => $request->filled('q') ? $request->string('q')->toString() : null,
            'status' => $request->filled('status') ? $request->string('status')->toString() : null,
        ]);

        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ArrivalStatus::class)],
        ]);

        return $this->list($request, 'Supply/Arrivals/Index', null, 'Arrivages');
    }

    public function pending(Request $request): Response
    {
        return $this->list($request, 'Supply/Arrivals/Pending', ArrivalStatus::Pending, 'Arrivages en attente');
    }

    public function history(Request $request): Response
    {
        $this->authorize('viewAny', Arrival::class);

        $request->merge([
            'status' => $request->filled('status') ? $request->string('status')->toString() : null,
        ]);

        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([ArrivalStatus::Validated->value, ArrivalStatus::Rejected->value])],
        ]);

        return $this->list(
            $request,
            'Supply/Arrivals/History',
            $request->filled('status') ? ArrivalStatus::from($request->string('status')->toString()) : null,
            'Historique des arrivages',
            [ArrivalStatus::Validated, ArrivalStatus::Rejected],
        );
    }

    public function create(Request $request, LocationProvisioner $locations): Response
    {
        $this->authorize('create', Arrival::class);

        $organization = $request->user()->organization;
        $products = Product::query()
            ->forOrganization($organization->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Supply/Arrivals/Create', [
            'products' => $products,
            'locations' => [
                CatalogPresenter::location($locations->boutique($organization)),
                CatalogPresenter::location($locations->depot($organization)),
            ],
        ]);
    }

    public function store(StoreArrivalRequest $request, ArrivalService $arrivals): RedirectResponse
    {
        try {
            $arrival = $arrivals->record(
                $request->user(),
                $request->product(),
                $request->location(),
                $request->integer('quantity'),
                $request->input('supplier_reference'),
            );
        } catch (InactiveProductException $exception) {
            throw ValidationException::withMessages(['product_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('arrivals.show', $arrival)
            ->with('status', 'Arrivage enregistré. Le stock n’augmentera qu’après validation d’un patron.');
    }

    public function show(Request $request, Arrival $arrival): Response
    {
        $this->authorize('view', $arrival);

        $arrival->load(['product', 'location', 'recorder', 'validator', 'rejector']);

        return Inertia::render('Supply/Arrivals/Show', [
            'arrival' => ArrivalPresenter::arrival($arrival),
            'canApprove' => $request->user()->can('approve', $arrival) && $arrival->isPending(),
        ]);
    }

    public function approve(ApproveArrivalRequest $request, Arrival $arrival, ArrivalService $arrivals): RedirectResponse
    {
        try {
            $arrivals->approve($request->user(), $arrival);
        } catch (ArrivalAlreadyProcessedException $exception) {
            throw ValidationException::withMessages(['arrival' => $exception->getMessage()]);
        } catch (InactiveProductException $exception) {
            throw ValidationException::withMessages(['arrival' => $exception->getMessage()]);
        }

        return redirect()
            ->route('arrivals.show', $arrival)
            ->with('status', 'Arrivage validé. Le stock a été augmenté.');
    }

    public function reject(RejectArrivalRequest $request, Arrival $arrival, ArrivalService $arrivals): RedirectResponse
    {
        try {
            $arrivals->reject(
                $request->user(),
                $arrival,
                $request->string('rejection_reason')->toString(),
            );
        } catch (ArrivalAlreadyProcessedException $exception) {
            throw ValidationException::withMessages(['rejection_reason' => $exception->getMessage()]);
        }

        return redirect()
            ->route('arrivals.show', $arrival)
            ->with('status', 'Arrivage rejeté. Le stock n’a pas été modifié.');
    }

    /**
     * @param  list<ArrivalStatus>|null  $statusGroup
     */
    private function list(
        Request $request,
        string $page,
        ?ArrivalStatus $forcedStatus,
        string $title,
        ?array $statusGroup = null,
    ): Response {
        $this->authorize('viewAny', Arrival::class);

        $user = $request->user();
        $canApprove = $user->hasPermission(Permission::ValidateStockReceipts);

        $query = Arrival::query()
            ->forOrganization($user->organization_id)
            ->with(['product', 'location', 'recorder', 'validator', 'rejector'])
            ->when(! $canApprove, fn ($builder) => $builder->where('recorded_by', $user->id))
            ->when($forcedStatus !== null, fn ($builder) => $builder->where('status', $forcedStatus))
            ->when($statusGroup !== null && $forcedStatus === null, fn ($builder) => $builder->whereIn('status', $statusGroup))
            ->when($forcedStatus === null && $statusGroup === null && $request->filled('status'), function ($builder) use ($request): void {
                $builder->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('q'), function ($builder) use ($request): void {
                $term = $request->string('q')->toString();
                $builder->whereHas('product', fn ($product) => $product->search($term));
            })
            ->latest('id');

        $results = $query->paginate(15)->withQueryString();

        return Inertia::render($page, [
            'title' => $title,
            'arrivals' => [
                'data' => $results->getCollection()->map(fn (Arrival $arrival) => ArrivalPresenter::arrival($arrival))->all(),
                'links' => $results->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'total' => $results->total(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                ],
            ],
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $forcedStatus?->value ?? $request->string('status')->toString(),
            ],
            'canCreate' => $user->can('create', Arrival::class),
            'canApprove' => $canApprove,
        ]);
    }
}
