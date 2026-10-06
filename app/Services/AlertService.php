<?php

namespace App\Services;

use App\Enums\ArrivalStatus;
use App\Enums\CustomerRequestPriority;
use App\Enums\CustomerRequestStatus;
use App\Enums\Permission;
use App\Enums\RentalStatus;
use App\Enums\SaleStatus;
use App\Models\Arrival;
use App\Models\CustomerRequest;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class AlertService
{
    public function __construct(
        private readonly LocationProvisioner $locations,
        private readonly RentalService $rentals,
    ) {}

    /**
     * @return list<array{type: string, title: string, message: string, href: string}>
     */
    public function forUser(User $user): array
    {
        if ($user->hasPermission(Permission::ManageRentals)) {
            $this->rentals->syncExpired($user);
        }

        $alerts = [];
        $includeAmounts = $user->canViewCompanyFinance();

        if ($user->hasPermission(Permission::SearchProducts)) {
            $alerts = [...$alerts, ...$this->lowStock($user)];
        }

        if ($user->hasPermission(Permission::CreateCustomerRequests)) {
            $alerts = [
                ...$alerts,
                ...$this->highDemand($user),
                ...$this->lowDemand($user),
                ...$this->repeatedRequests($user),
                ...$this->urgentRequests($user),
            ];
        }

        if ($user->hasPermission(Permission::RecordStockReceipts)) {
            $alerts = [...$alerts, ...$this->pendingArrivals($user)];
        }

        $alerts = [...$alerts, ...$this->rentalExpirations($user, $includeAmounts)];

        return $alerts;
    }

    /**
     * @return list<array{type: string, title: string, message: string, href: string}>
     */
    private function lowStock(User $user): array
    {
        $boutique = $this->locations->boutique($user->organization);

        $rows = Inventory::query()
            ->where('inventories.location_id', $boutique->id)
            ->where('inventories.quantity', '>', 0)
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('products.is_active', true)
            ->whereColumn('inventories.quantity', '<=', 'products.low_stock_threshold')
            ->orderBy('products.name')
            ->get(['products.id as product_id', 'products.name', 'products.code', 'inventories.quantity', 'products.low_stock_threshold']);

        return $rows->map(fn ($row): array => [
            'type' => 'low_stock',
            'title' => 'Stock faible',
            'message' => $row->name.' ('.$row->code.') : '.$row->quantity.' en boutique, seuil '.$row->low_stock_threshold.'.',
            'href' => route('products.show', $row->product_id),
        ])->all();
    }

    /**
     * @return list<array{type: string, title: string, message: string, href: string}>
     */
    private function highDemand(User $user): array
    {
        $rows = CustomerRequest::query()
            ->where('organization_id', $user->organization_id)
            ->where('requested_at', '>=', now()->subDays(30))
            ->select('product_id', DB::raw('COUNT(*) as total'))
            ->groupBy('product_id')
            ->havingRaw('COUNT(*) >= 3')
            ->with('product:id,name,code')
            ->get();

        return $rows->map(fn (CustomerRequest $row): array => [
            'type' => 'high_demand',
            'title' => 'Forte demande',
            'message' => ($row->product?->name ?? 'Article').' a été demandé '.$row->total.' fois sur 30 jours.',
            'href' => route('requests.index', ['product_id' => $row->product_id]),
        ])->all();
    }

    /**
     * @return list<array{type: string, title: string, message: string, href: string}>
     */
    private function lowDemand(User $user): array
    {
        $boutique = $this->locations->boutique($user->organization);

        $products = Product::query()
            ->forOrganization($user->organization_id)
            ->where('is_active', true)
            ->where('created_at', '<=', now()->subDays(30))
            ->whereHas('inventories', fn ($query) => $query
                ->where('location_id', $boutique->id)
                ->where('quantity', '>', 0))
            ->whereDoesntHave('sales', fn ($query) => $query
                ->where('status', SaleStatus::Completed)
                ->where('sold_at', '>=', now()->subDays(90)))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'code']);

        return $products->map(fn (Product $product): array => [
            'type' => 'low_demand',
            'title' => 'Faible demande',
            'message' => $product->name.' ('.$product->code.') n’a aucune vente conclue sur 90 jours et reste en stock boutique.',
            'href' => route('products.show', $product),
        ])->all();
    }

    /**
     * @return list<array{type: string, title: string, message: string, href: string}>
     */
    private function repeatedRequests(User $user): array
    {
        $rows = CustomerRequest::query()
            ->where('organization_id', $user->organization_id)
            ->where('status', CustomerRequestStatus::Open)
            ->select('product_id', DB::raw('COUNT(*) as total'))
            ->groupBy('product_id')
            ->havingRaw('COUNT(*) >= 2')
            ->with('product:id,name')
            ->get();

        return $rows->map(fn (CustomerRequest $row): array => [
            'type' => 'repeated_request',
            'title' => 'Demandes répétées',
            'message' => ($row->product?->name ?? 'Article').' a '.$row->total.' demandes ouvertes.',
            'href' => route('requests.index', ['product_id' => $row->product_id, 'status' => CustomerRequestStatus::Open->value]),
        ])->all();
    }

    /**
     * @return list<array{type: string, title: string, message: string, href: string}>
     */
    private function urgentRequests(User $user): array
    {
        $rows = CustomerRequest::query()
            ->forOrganization($user->organization_id)
            ->where('status', CustomerRequestStatus::Open)
            ->where('priority', CustomerRequestPriority::Urgent)
            ->with('product:id,name')
            ->orderByDesc('requested_at')
            ->limit(20)
            ->get();

        return $rows->map(fn (CustomerRequest $request): array => [
            'type' => 'urgent_request',
            'title' => 'Demande urgente',
            'message' => 'Demande urgente pour '.($request->product?->name ?? 'un article').'.',
            'href' => route('requests.show', $request),
        ])->all();
    }

    /**
     * @return list<array{type: string, title: string, message: string, href: string}>
     */
    private function pendingArrivals(User $user): array
    {
        $query = Arrival::query()
            ->forOrganization($user->organization_id)
            ->where('status', ArrivalStatus::Pending);

        if (! $user->hasPermission(Permission::ValidateStockReceipts)) {
            $query->where('recorded_by', $user->id);
        }

        $count = $query->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'type' => 'pending_arrival',
            'title' => 'Réception en attente',
            'message' => $count.' arrivage(s) en attente de traitement.',
            'href' => route('arrivals.pending'),
        ]];
    }

    /**
     * @return list<array{type: string, title: string, message: string, href: string}>
     */
    private function rentalExpirations(User $user, bool $includeAmounts): array
    {
        $until = now()->addDays(30)->toDateString();
        $today = now()->toDateString();

        $rentals = Rental::query()
            ->forOrganization($user->organization_id)
            ->where('status', RentalStatus::Active)
            ->whereDate('ends_on', '>=', $today)
            ->whereDate('ends_on', '<=', $until)
            ->orderBy('ends_on')
            ->get();

        return $rentals->map(function (Rental $rental) use ($includeAmounts): array {
            $date = $rental->ends_on?->timezone(config('app.timezone'))->format('d/m/Y');
            $message = 'La location « '.$rental->label.' » arrive à échéance le '.$date.'.';

            if ($includeAmounts) {
                $message = 'La location « '.$rental->label.' » ('.$this->amount($rental).') arrive à échéance le '.$date.'.';
            }

            return [
                'type' => 'rental_expiration',
                'title' => 'Échéance de location',
                'message' => $message,
                'href' => $includeAmounts
                    ? route('rentals.show', $rental)
                    : route('alerts.index'),
            ];
        })->all();
    }

    private function amount(Rental $rental): string
    {
        return Money::normalize($rental->amount);
    }
}
