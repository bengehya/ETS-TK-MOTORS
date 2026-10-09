<?php

namespace App\Http\Controllers\Sales;

use App\Enums\Currency;
use App\Enums\Permission;
use App\Enums\SaleStatus;
use App\Exceptions\DuplicateSaleException;
use App\Exceptions\InactiveProductException;
use App\Exceptions\InsufficientCashException;
use App\Exceptions\InsufficientPaymentException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\MissingPurchasePriceException;
use App\Exceptions\SaleAlreadyCancelledException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\CancelSaleRequest;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\LocationProvisioner;
use App\Services\SaleService;
use App\Support\Money;
use App\Support\OperationsPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $includeFinance = $user->canViewCompanyFinance();

        $sales = Sale::query()
            ->forOrganization($user->organization_id)
            ->with(['product:id,code,name,barcode', 'lines.product:id,code,name,barcode', 'seller', 'location:id,name', 'canceller'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $like = $this->like($request->string('q')->toString());
                $query->where(function ($inner) use ($like): void {
                    $inner->where('reference', 'like', $like)
                        ->orWhereHas('product', function ($product) use ($like): void {
                            $product->where('name', 'like', $like)
                                ->orWhere('code', 'like', $like)
                                ->orWhere('barcode', 'like', $like);
                        });
                });
            })
            ->when($request->filled('seller_id'), fn ($query) => $query->where('seller_id', $request->integer('seller_id')))
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->where('sold_at', '>=', $request->date('date_from')->startOfDay()))
            ->when($request->filled('date_to'), fn ($query) => $query->where('sold_at', '<=', $request->date('date_to')->endOfDay()))
            ->orderByDesc('sold_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => [
                'data' => $sales->getCollection()->map(fn (Sale $sale) => OperationsPresenter::sale($sale, $includeFinance))->all(),
                'links' => $sales->linkCollection()->toArray(),
            ],
            'filters' => [
                'q' => $request->string('q')->toString(),
                'seller_id' => $request->string('seller_id')->toString(),
                'product_id' => $request->string('product_id')->toString(),
                'status' => $request->string('status')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
            ],
            'statuses' => collect(SaleStatus::cases())->map(fn (SaleStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
            'sellers' => User::query()
                ->where('organization_id', $user->organization_id)
                ->orderBy('name')
                ->get()
                ->map(fn (User $seller) => ['id' => $seller->id, 'name' => $seller->displayName()])
                ->values(),
            'canCancel' => $user->hasPermission(Permission::CancelSales),
            'includeFinance' => $includeFinance,
        ]);
    }

    public function create(Request $request, LocationProvisioner $locations): Response
    {
        $user = $request->user();
        $term = $request->string('q')->toString();

        return Inertia::render('Sales/Create', [
            'filters' => ['q' => $term],
            'matches' => $this->matches($user, $term, $locations),
            'includeFinance' => $user->canViewCompanyFinance(),
        ]);
    }

    public function store(StoreSaleRequest $request, SaleService $sales): RedirectResponse
    {
        $lines = collect($request->validated('lines'))
            ->map(function (array $line) use ($request): array {
                $product = Product::query()
                    ->forOrganization($request->user()->organization_id)
                    ->findOrFail($line['product_id']);

                return [
                    'product' => $product,
                    'quantity' => (int) $line['quantity'],
                ];
            })
            ->all();

        try {
            $sale = $sales->sellCart(
                $request->user(),
                $lines,
                Currency::from($request->string('currency')->toString()),
                $request->filled('amount_received') ? (string) $request->input('amount_received') : null,
                $request->filled('client_token') ? $request->string('client_token')->toString() : null,
            );
        } catch (Throwable $exception) {
            $this->abortSale($exception, $request->input('lines', []));
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', 'Vente '.$sale->reference.' enregistrée.')
            ->with('sale_confirmed', true);
    }

    public function show(Request $request, Sale $sale): Response
    {
        $sale->load(['product', 'seller', 'location', 'canceller', 'lines.product']);
        $includeFinance = $request->user()->canViewCompanyFinance();

        return Inertia::render('Sales/Show', [
            'sale' => OperationsPresenter::sale($sale, $includeFinance),
            'canCancel' => $request->user()->hasPermission(Permission::CancelSales) && ! $sale->isCancelled(),
            'includeFinance' => $includeFinance,
            'confirmed' => (bool) $request->session()->get('sale_confirmed', false),
        ]);
    }

    public function invoice(Request $request, Sale $sale): Response
    {
        $sale->load(['product', 'seller', 'location', 'lines.product']);

        return Inertia::render('Sales/Invoice', [
            'sale' => OperationsPresenter::sale($sale, false),
            'company' => [
                'name' => config('tkmotors.company'),
                'slogan' => config('tkmotors.slogan'),
                'city' => config('tkmotors.city'),
                'logo' => '/images/logo.jpg',
            ],
        ]);
    }

    public function cancel(CancelSaleRequest $request, Sale $sale, SaleService $sales): RedirectResponse
    {
        try {
            $sales->cancel($request->user(), $sale, $request->string('reason')->toString());
        } catch (Throwable $exception) {
            $this->abortSale($exception, []);
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', 'Vente annulée. Le stock boutique et la caisse ont été rétablis.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function matches(User $user, string $term, LocationProvisioner $locations): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $boutique = $locations->boutique($user->organization);
        $includeFinance = $user->canViewCompanyFinance();

        return Product::query()
            ->forOrganization($user->organization_id)
            ->where('is_active', true)
            ->search($term)
            ->with(['inventories' => fn ($query) => $query->where('location_id', $boutique->id)])
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function (Product $product) use ($includeFinance): array {
                $row = [
                    'id' => $product->id,
                    'code' => $product->code,
                    'barcode' => $product->barcode,
                    'name' => $product->name,
                    'sale_price' => Money::normalize($product->sale_price),
                    'boutique_quantity' => (int) ($product->inventories->first()->quantity ?? 0),
                ];

                if ($includeFinance) {
                    $row['purchase_price'] = $product->purchase_price === null
                        ? null
                        : Money::normalize($product->purchase_price);
                }

                return $row;
            })
            ->all();
    }

    private function like(string $term): string
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], trim($term));

        return '%'.$escaped.'%';
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function abortSale(Throwable $exception, array $lines): never
    {
        $productId = property_exists($exception, 'productId') ? $exception->productId : null;
        $index = 0;

        foreach ($lines as $position => $line) {
            if ($productId !== null && (int) ($line['product_id'] ?? 0) === $productId) {
                $index = (int) $position;
                break;
            }
        }

        $field = match (true) {
            $exception instanceof MissingPurchasePriceException,
            $exception instanceof InactiveProductException => count($lines) > 1 ? 'lines.'.$index.'.product_id' : 'product_id',
            $exception instanceof InsufficientStockException => count($lines) > 1 ? 'lines.'.$index.'.quantity' : 'quantity',
            $exception instanceof InsufficientPaymentException => 'amount_received',
            $exception instanceof DuplicateSaleException => 'client_token',
            $exception instanceof SaleAlreadyCancelledException,
            $exception instanceof InsufficientCashException => 'reason',
            default => throw $exception,
        };

        throw ValidationException::withMessages([
            $field => $exception->getMessage(),
        ]);
    }
}
