<?php

namespace App\Services;

use App\Enums\ArrivalStatus;
use App\Enums\ExpenseStatus;
use App\Enums\Permission;
use App\Enums\SaleStatus;
use App\Models\Arrival;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\ArrivalPresenter;
use App\Support\Money;
use App\Support\OperationsPresenter;
use App\Support\UserPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function payload(User $user, string $periode, LocationProvisioner $locations): array
    {
        $periode = $this->normalizePeriode($periode);
        $organization = $user->organization;
        $canViewCatalog = $user->hasPermission(Permission::SearchProducts);
        $canRecordArrivals = $user->hasPermission(Permission::RecordStockReceipts);
        $canValidateArrivals = $user->hasPermission(Permission::ValidateStockReceipts);
        $canViewFinance = $user->canViewCompanyFinance();

        $payload = [
            'welcome' => UserPresenter::welcome($user),
            'profile' => UserPresenter::identity($user),
            'periode' => $periode,
            'periodes' => [
                ['value' => 'jour', 'label' => 'Jour'],
                ['value' => 'semaine', 'label' => 'Semaine'],
                ['value' => 'mois', 'label' => 'Mois'],
                ['value' => 'annee', 'label' => 'Année'],
            ],
            'canViewCatalog' => $canViewCatalog,
            'canRecordArrivals' => $canRecordArrivals,
            'canValidateArrivals' => $canValidateArrivals,
            'canViewFinance' => $canViewFinance,
            'stock' => $canViewCatalog
                ? $this->stockSummary($user, $locations)
                : null,
            'arrivals' => $canRecordArrivals
                ? $this->arrivalSummary($user, $canValidateArrivals)
                : null,
        ];

        if ($canViewFinance) {
            $payload['finance'] = $this->financeSummary($organization->id, $periode);
        }

        return $payload;
    }

    public function normalizePeriode(string $periode): string
    {
        return in_array($periode, ['jour', 'semaine', 'mois', 'annee'], true)
            ? $periode
            : 'jour';
    }

    /**
     * @return array<string, mixed>
     */
    private function stockSummary(User $user, LocationProvisioner $locations): array
    {
        $organization = $user->organization;
        $boutique = $locations->boutique($organization);
        $depot = $locations->depot($organization);

        $exhausted = Inventory::query()
            ->where('location_id', $boutique->id)
            ->where('quantity', 0)
            ->whereHas('product', fn ($query) => $query
                ->forOrganization($organization->id)
                ->where('is_active', true))
            ->with('product:id,code,name')
            ->orderBy('product_id')
            ->get();

        return [
            'boutique_quantity' => (int) Inventory::query()->where('location_id', $boutique->id)->sum('quantity'),
            'depot_quantity' => (int) Inventory::query()->where('location_id', $depot->id)->sum('quantity'),
            'active_products' => Product::query()
                ->forOrganization($organization->id)
                ->where('is_active', true)
                ->count(),
            'low_stock' => $this->lowStock($organization->id, $boutique->id),
            'exhausted' => [
                'count' => $exhausted->count(),
                'items' => $exhausted->take(8)->map(fn (Inventory $inventory) => [
                    'id' => $inventory->product?->id,
                    'code' => $inventory->product?->code,
                    'name' => $inventory->product?->name,
                    'quantity' => 0,
                ])->values()->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function arrivalSummary(User $user, bool $canValidate): array
    {
        $query = Arrival::query()
            ->forOrganization($user->organization_id)
            ->when(! $canValidate, fn ($builder) => $builder->where('recorded_by', $user->id));

        $pending = (clone $query)->where('status', ArrivalStatus::Pending)->count();

        $recent = fn (ArrivalStatus $status) => (clone $query)
            ->where('status', $status)
            ->with(['product', 'location', 'recorder', 'validator', 'rejector'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Arrival $arrival) => ArrivalPresenter::arrival($arrival))
            ->all();

        return [
            'pending' => $pending,
            'validated' => $recent(ArrivalStatus::Validated),
            'rejected' => $recent(ArrivalStatus::Rejected),
        ];
    }

    /**
     * @return array{threshold_defined: bool, count: int, message: string, items: list<array<string, mixed>>}
     */
    private function lowStock(int $organizationId, int $boutiqueId): array
    {
        $rows = Inventory::query()
            ->where('inventories.location_id', $boutiqueId)
            ->where('inventories.quantity', '>', 0)
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('products.organization_id', $organizationId)
            ->where('products.is_active', true)
            ->whereColumn('inventories.quantity', '<=', 'products.low_stock_threshold')
            ->orderBy('products.name')
            ->get([
                'products.id as product_id',
                'products.code',
                'products.name',
                'inventories.quantity',
                'products.low_stock_threshold',
            ]);

        $count = $rows->count();

        return [
            'threshold_defined' => true,
            'count' => $count,
            'message' => $count === 0
                ? 'Aucun article actif en boutique n’est au seuil de stock ou en dessous.'
                : $count.' article(s) actif(s) en boutique sont au seuil de stock ou en dessous.',
            'items' => $rows->take(8)->map(fn ($row) => [
                'id' => $row->product_id,
                'code' => $row->code,
                'name' => $row->name,
                'quantity' => (int) $row->quantity,
                'threshold' => (int) $row->low_stock_threshold,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function financeSummary(int $organizationId, string $periode): array
    {
        $range = $this->periodeRange($periode);

        $completed = Sale::query()
            ->where('organization_id', $organizationId)
            ->where('status', SaleStatus::Completed);

        $todayStart = now(config('app.timezone'))->startOfDay();
        $today = (clone $completed)->where('sold_at', '>=', $todayStart);
        $period = (clone $completed)->whereBetween('sold_at', [$range['start'], $range['end']]);

        $ranked = (clone $completed)
            ->select('product_id', DB::raw('SUM(quantity) as quantity_sold'), DB::raw('SUM(line_total) as amount_sold'))
            ->groupBy('product_id')
            ->orderByDesc('quantity_sold')
            ->with('product:id,code,name')
            ->get();

        $mapRanked = fn ($items) => $items->map(fn (Sale $row) => [
            'id' => $row->product?->id,
            'code' => $row->product?->code,
            'name' => $row->product?->name,
            'quantity_sold' => (int) $row->quantity_sold,
            'amount' => Money::normalize($row->amount_sold),
        ])->values()->all();

        $validatedExpenses = Expense::query()
            ->where('organization_id', $organizationId)
            ->where('status', ExpenseStatus::Validated)
            ->whereBetween('spent_on', [$range['start']->toDateString(), $range['end']->toDateString()]);

        return [
            'sales' => [
                'available' => true,
                'today_count' => (clone $today)->count(),
                'today_quantity' => (int) (clone $today)->sum('quantity'),
                'today_amount' => Money::normalize((clone $today)->sum('line_total')),
                'period_count' => (clone $period)->count(),
                'period_quantity' => (int) (clone $period)->sum('quantity'),
                'period_amount' => Money::normalize((clone $period)->sum('line_total')),
            ],
            'profit' => [
                'available' => true,
                'amount' => Money::normalize((clone $period)->sum('profit')),
            ],
            'cash' => [
                'available' => true,
                'amount' => app(CashService::class)->displayedBalance($organizationId),
            ],
            'expenses' => [
                'available' => true,
                'total' => Money::normalize((clone $validatedExpenses)->sum('amount')),
                'recent' => (clone $validatedExpenses)
                    ->with('creator')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Expense $expense) => OperationsPresenter::expense($expense))
                    ->all(),
            ],
            'top_sold' => $mapRanked($ranked->take(5)),
            'least_sold' => $mapRanked($ranked->sortBy('quantity_sold')->take(5)),
            'chart' => $this->chart($periode, $range, (clone $period)->get(['sold_at', 'quantity', 'line_total'])),
        ];
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable, label: string}
     */
    private function periodeRange(string $periode): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        return match ($periode) {
            'semaine' => [
                'start' => $now->startOfWeek(),
                'end' => $now->endOfWeek(),
                'label' => 'Ventes par jour',
            ],
            'mois' => [
                'start' => $now->startOfMonth(),
                'end' => $now->endOfMonth(),
                'label' => 'Ventes par jour',
            ],
            'annee' => [
                'start' => $now->startOfYear(),
                'end' => $now->endOfYear(),
                'label' => 'Ventes par mois',
            ],
            default => [
                'start' => $now->startOfDay(),
                'end' => $now->endOfDay(),
                'label' => 'Ventes par heure',
            ],
        };
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, label: string}  $range
     * @param  Collection<int, Sale>  $sales
     * @return array<string, mixed>
     */
    private function chart(string $periode, array $range, $sales): array
    {
        $buckets = $this->emptyBuckets($periode, $range['start']);

        foreach ($sales as $sale) {
            $at = $sale->sold_at?->timezone(config('app.timezone'));
            if ($at === null) {
                continue;
            }

            $key = match ($periode) {
                'jour' => $at->format('H'),
                'annee' => $at->format('Y-m'),
                default => $at->toDateString(),
            };

            if (isset($buckets[$key])) {
                $buckets[$key]['quantity'] += (int) $sale->quantity;
                $buckets[$key]['amount'] = Money::add($buckets[$key]['amount'], Money::normalize($sale->line_total));
                $buckets[$key]['count']++;
            }
        }

        $points = array_values($buckets);
        $hasData = collect($points)->contains(fn (array $point) => $point['quantity'] > 0);

        return [
            'label' => $range['label'],
            'empty' => ! $hasData,
            'points' => $hasData ? $points : [],
        ];
    }

    /**
     * @return array<string, array{key: string, label: string, quantity: int, count: int, amount: string}>
     */
    private function emptyBuckets(string $periode, CarbonImmutable $start): array
    {
        $buckets = [];

        if ($periode === 'jour') {
            for ($hour = 0; $hour < 24; $hour++) {
                $key = sprintf('%02d', $hour);
                $buckets[$key] = ['key' => $key, 'label' => $key.'h', 'quantity' => 0, 'count' => 0, 'amount' => '0.00'];
            }

            return $buckets;
        }

        if ($periode === 'annee') {
            for ($month = 1; $month <= 12; $month++) {
                $cursor = $start->month($month);
                $key = $cursor->format('Y-m');
                $buckets[$key] = ['key' => $key, 'label' => $cursor->isoFormat('MMM'), 'quantity' => 0, 'count' => 0, 'amount' => '0.00'];
            }

            return $buckets;
        }

        $days = $periode === 'semaine' ? 7 : $start->daysInMonth;
        for ($i = 0; $i < $days; $i++) {
            $cursor = $start->addDays($i);
            $key = $cursor->toDateString();
            $buckets[$key] = ['key' => $key, 'label' => $cursor->isoFormat('DD/MM'), 'quantity' => 0, 'count' => 0, 'amount' => '0.00'];
        }

        return $buckets;
    }
}
