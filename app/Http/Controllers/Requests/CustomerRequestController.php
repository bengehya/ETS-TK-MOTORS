<?php

namespace App\Http\Controllers\Requests;

use App\Enums\CustomerRequestPriority;
use App\Enums\CustomerRequestStatus;
use App\Exceptions\InactiveProductException;
use App\Exceptions\OperationAlreadyProcessedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\CloseCustomerRequestRequest;
use App\Http\Requests\Requests\StoreCustomerRequestRequest;
use App\Models\CustomerRequest;
use App\Models\Product;
use App\Services\CustomerRequestService;
use App\Support\OperationsPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $requests = CustomerRequest::query()
            ->forOrganization($request->user()->organization_id)
            ->with(['product:id,code,name', 'recorder', 'closer'])
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')->toString()))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->string('q')->toString())).'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('customer_name', 'like', $like)
                        ->orWhereHas('product', fn ($product) => $product
                            ->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like)
                            ->orWhere('barcode', 'like', $like));
                });
            })
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Requests/Index', [
            'requests' => [
                'data' => $requests->getCollection()->map(fn (CustomerRequest $item) => OperationsPresenter::customerRequest($item))->all(),
                'links' => $requests->linkCollection()->toArray(),
            ],
            'filters' => [
                'q' => $request->string('q')->toString(),
                'product_id' => $request->string('product_id')->toString(),
                'status' => $request->string('status')->toString(),
                'priority' => $request->string('priority')->toString(),
            ],
            'statuses' => collect(CustomerRequestStatus::cases())->map(fn (CustomerRequestStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
            'priorities' => collect(CustomerRequestPriority::cases())->map(fn (CustomerRequestPriority $priority) => [
                'value' => $priority->value,
                'label' => $priority->label(),
            ])->values(),
        ]);
    }

    public function create(Request $request): Response
    {
        $term = trim($request->string('q')->toString());
        $matches = [];

        if ($term !== '') {
            $matches = Product::query()
                ->forOrganization($request->user()->organization_id)
                ->where('is_active', true)
                ->search($term)
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'code', 'barcode', 'name'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'code' => $product->code,
                    'barcode' => $product->barcode,
                    'name' => $product->name,
                ])
                ->all();
        }

        return Inertia::render('Requests/Create', [
            'filters' => ['q' => $term],
            'matches' => $matches,
        ]);
    }

    public function store(StoreCustomerRequestRequest $request, CustomerRequestService $requests): RedirectResponse
    {
        $product = Product::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($request->integer('product_id'));

        try {
            $recorded = $requests->record(
                $request->user(),
                $product,
                $request->string('customer_name')->toString(),
                $request->filled('quantity') ? $request->integer('quantity') : null,
                $request->boolean('urgent'),
                $request->string('notes')->toString(),
            );
        } catch (InactiveProductException $exception) {
            throw ValidationException::withMessages(['product_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('requests.show', $recorded)
            ->with('status', 'Demande enregistrée.');
    }

    public function show(CustomerRequest $customerRequest): Response
    {
        $customerRequest->load(['product', 'recorder', 'closer']);

        return Inertia::render('Requests/Show', [
            'customerRequest' => OperationsPresenter::customerRequest($customerRequest),
        ]);
    }

    public function fulfill(CloseCustomerRequestRequest $request, CustomerRequest $customerRequest, CustomerRequestService $requests): RedirectResponse
    {
        try {
            $requests->fulfill($request->user(), $customerRequest, $request->string('note')->toString());
        } catch (OperationAlreadyProcessedException $exception) {
            throw ValidationException::withMessages(['note' => $exception->getMessage()]);
        }

        return redirect()
            ->route('requests.show', $customerRequest)
            ->with('status', 'Demande marquée comme satisfaite.');
    }

    public function cancel(CloseCustomerRequestRequest $request, CustomerRequest $customerRequest, CustomerRequestService $requests): RedirectResponse
    {
        try {
            $requests->cancel($request->user(), $customerRequest, $request->string('note')->toString());
        } catch (OperationAlreadyProcessedException $exception) {
            throw ValidationException::withMessages(['note' => $exception->getMessage()]);
        }

        return redirect()
            ->route('requests.show', $customerRequest)
            ->with('status', 'Demande annulée. L’historique est conservé.');
    }
}
