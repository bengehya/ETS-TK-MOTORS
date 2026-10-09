<?php

namespace App\Http\Controllers\Requests;

use App\Enums\CustomerRequestPriority;
use App\Enums\CustomerRequestStatus;
use App\Enums\Permission;
use App\Enums\RestockSuggestionStatus;
use App\Exceptions\InactiveProductException;
use App\Exceptions\OperationAlreadyProcessedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\CloseCustomerRequestRequest;
use App\Http\Requests\Requests\StoreCustomerRequestRequest;
use App\Http\Requests\Requests\UpdateRestockSuggestionRequest;
use App\Models\CustomerRequest;
use App\Models\Product;
use App\Models\RestockSuggestion;
use App\Models\User;
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
        $user = $request->user();
        $canManage = $user->hasPermission(Permission::ManageSales);

        $requests = CustomerRequest::query()
            ->forOrganization($user->organization_id)
            ->with(['product:id,code,name', 'recorder', 'closer'])
            ->when(! $canManage, fn ($query) => $query->where('recorded_by', $user->id))
            ->when($canManage && $request->filled('author_id'), fn ($query) => $query->where('recorded_by', $request->integer('author_id')))
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->where('requested_at', '>=', $request->date('date_from')->startOfDay()))
            ->when($request->filled('date_to'), fn ($query) => $query->where('requested_at', '<=', $request->date('date_to')->endOfDay()))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->string('q')->toString())).'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('customer_name', 'like', $like)
                        ->orWhere('designation', 'like', $like)
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
                'author_id' => $request->string('author_id')->toString(),
                'status' => $request->string('status')->toString(),
                'priority' => $request->string('priority')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
            ],
            'canManage' => $canManage,
            'authors' => $canManage
                ? User::query()->where('organization_id', $user->organization_id)->orderBy('name')->get()->map(fn (User $author) => [
                    'id' => $author->id,
                    'name' => $author->displayName(),
                ])->values()
                : [],
            'suggestions' => RestockSuggestion::query()
                ->forOrganization($user->organization_id)
                ->with(['product:id,code,name', 'decider'])
                ->when(! $canManage, fn ($query) => $query->whereIn('status', [
                    RestockSuggestionStatus::Retained->value,
                    RestockSuggestionStatus::Decided->value,
                ]))
                ->orderByDesc('last_requested_at')
                ->limit(20)
                ->get()
                ->map(fn (RestockSuggestion $suggestion) => OperationsPresenter::restockSuggestion($suggestion))
                ->all(),
            'restock' => [
                'observation_days' => (int) config('tkmotors.restock.observation_days'),
                'repeat_threshold' => (int) config('tkmotors.restock.repeat_threshold'),
            ],
            'suggestionStatuses' => collect(RestockSuggestionStatus::cases())
                ->reject(fn (RestockSuggestionStatus $status) => $status === RestockSuggestionStatus::Open)
                ->map(fn (RestockSuggestionStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])->values(),
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
        $product = $request->filled('product_id')
            ? Product::query()->forOrganization($request->user()->organization_id)->findOrFail($request->integer('product_id'))
            : null;

        try {
            $recorded = $requests->record(
                $request->user(),
                $product,
                $request->string('customer_name')->toString(),
                $request->integer('quantity'),
                $request->boolean('urgent'),
                $request->string('notes')->toString(),
                $request->string('designation')->toString(),
            );
        } catch (InactiveProductException $exception) {
            throw ValidationException::withMessages(['product_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('requests.show', $recorded)
            ->with('status', 'Demande enregistrée.');
    }

    public function show(Request $request, CustomerRequest $customerRequest): Response
    {
        $user = $request->user();
        $canManage = $user->hasPermission(Permission::ManageSales);

        if (! $canManage && $customerRequest->recorded_by !== $user->id) {
            abort(403, 'Action non autorisée.');
        }

        $customerRequest->load(['product', 'recorder', 'closer', 'events.user']);

        return Inertia::render('Requests/Show', [
            'customerRequest' => OperationsPresenter::customerRequest($customerRequest),
            'canTreat' => $canManage && $customerRequest->isOpen(),
        ]);
    }

    public function updateSuggestion(UpdateRestockSuggestionRequest $request, RestockSuggestion $restockSuggestion, CustomerRequestService $requests): RedirectResponse
    {
        try {
            $requests->decideSuggestion(
                $request->user(),
                $restockSuggestion,
                RestockSuggestionStatus::from($request->string('status')->toString()),
                $request->string('justification')->toString(),
            );
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['justification' => $exception->getMessage()]);
        }

        return redirect()
            ->route('requests.index')
            ->with('status', 'Suggestion mise à jour. Aucun achat ni mouvement de stock n’a été créé.');
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
