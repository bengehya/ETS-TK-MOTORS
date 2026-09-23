<?php

namespace App\Services;

use App\Enums\ArrivalStatus;
use App\Enums\Permission;
use App\Enums\StockMovementType;
use App\Models\Arrival;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\ArrivalPresenter;
use Carbon\CarbonImmutable;
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
        $canViewFinance = $user->hasPermission(Permission::ViewReports);

        $payload = [
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
            'low_stock' => [
                'threshold_defined' => false,
                'message' => 'Aucun seuil de stock faible n’est défini pour les articles.',
            ],
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
     * @return array<string, mixed>
     */
    private function financeSummary(int $organizationId, string $periode): array
    {
        $range = $this->periodeRange($periode);

        $saleMovements = StockMovement::query()
            ->where('organization_id', $organizationId)
            ->where('type', StockMovementType::Sale);

        $todayStart = now(config('app.timezone'))->startOfDay();
        $todaySales = (clone $saleMovements)
            ->where('created_at', '>=', $todayStart)
            ->selectRaw('COUNT(*) as movement_count, COALESCE(SUM(quantity), 0) as quantity')
            ->first();

        $ranked = (clone $saleMovements)
            ->select('product_id', DB::raw('SUM(quantity) as quantity_sold'))
            ->groupBy('product_id')
            ->orderByDesc('quantity_sold')
            ->with('product:id,code,name')
            ->get();

        $mapRanked = fn ($items) => $items->map(fn (StockMovement $row) => [
            'id' => $row->product?->id,
            'code' => $row->product?->code,
            'name' => $row->product?->name,
            'quantity_sold' => (int) $row->quantity_sold,
            'amount' => null,
        ])->values()->all();

        $periodSales = (clone $saleMovements)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->get(['created_at', 'quantity']);

        return [
            'sales' => [
                'available' => false,
                'reason' => 'Le module ventes n’est pas encore disponible. Aucun montant de vente n’est enregistré.',
                'today_count' => (int) ($todaySales?->movement_count ?? 0),
                'today_quantity' => (int) ($todaySales?->quantity ?? 0),
                'today_amount' => null,
            ],
            'profit' => [
                'available' => false,
                'reason' => 'Le bénéfice ne peut pas être calculé : aucun coût d’achat ni vente valorisée n’est enregistré.',
                'amount' => null,
            ],
            'cash' => [
                'available' => false,
                'reason' => 'Le module caisse n’est pas encore disponible.',
                'amount' => null,
            ],
            'expenses' => [
                'available' => false,
                'reason' => 'Le module dépenses n’est pas encore disponible.',
                'total' => null,
                'recent' => [],
            ],
            'top_sold' => $mapRanked($ranked->take(5)),
            'least_sold' => $mapRanked($ranked->sortBy('quantity_sold')->take(5)),
            'chart' => $this->chart($periode, $range, $periodSales),
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
     * @param  \Illuminate\Support\Collection<int, StockMovement>  $movements
     * @return array<string, mixed>
     */
    private function chart(string $periode, array $range, $movements): array
    {
        $buckets = $this->emptyBuckets($periode, $range['start']);

        foreach ($movements as $movement) {
            $at = $movement->created_at?->timezone(config('app.timezone'));
            if ($at === null) {
                continue;
            }

            $key = match ($periode) {
                'jour' => $at->format('H'),
                'annee' => $at->format('Y-m'),
                default => $at->toDateString(),
            };

            if (isset($buckets[$key])) {
                $buckets[$key]['quantity'] += (int) $movement->quantity;
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
     * @return array<string, array{key: string, label: string, quantity: int, count: int}>
     */
    private function emptyBuckets(string $periode, CarbonImmutable $start): array
    {
        $buckets = [];

        if ($periode === 'jour') {
            for ($hour = 0; $hour < 24; $hour++) {
                $key = sprintf('%02d', $hour);
                $buckets[$key] = ['key' => $key, 'label' => $key.'h', 'quantity' => 0, 'count' => 0];
            }

            return $buckets;
        }

        if ($periode === 'annee') {
            for ($month = 1; $month <= 12; $month++) {
                $cursor = $start->month($month);
                $key = $cursor->format('Y-m');
                $buckets[$key] = ['key' => $key, 'label' => $cursor->isoFormat('MMM'), 'quantity' => 0, 'count' => 0];
            }

            return $buckets;
        }

        $days = $periode === 'semaine' ? 7 : $start->daysInMonth;
        for ($i = 0; $i < $days; $i++) {
            $cursor = $start->addDays($i);
            $key = $cursor->toDateString();
            $buckets[$key] = ['key' => $key, 'label' => $cursor->isoFormat('DD/MM'), 'quantity' => 0, 'count' => 0];
        }

        return $buckets;
    }
}
