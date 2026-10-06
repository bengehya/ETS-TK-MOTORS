<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\SaleStatus;
use App\Exceptions\InactiveProductException;
use App\Exceptions\MissingPurchasePriceException;
use App\Exceptions\SaleAlreadyCancelledException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\Money;
use App\Support\ReferenceCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SaleService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CashService $cash,
        private readonly AuditLogger $audit,
        private readonly LocationProvisioner $locations,
    ) {}

    public function sell(User $user, Product $product, int $quantity): Sale
    {
        if (! $user->hasPermission(Permission::CreateSales)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        if ($user->organization_id !== $product->organization_id) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('La quantité doit être un entier positif.');
        }

        if (! $product->is_active) {
            throw new InactiveProductException('Cet article est désactivé.');
        }

        if ($product->purchase_price === null || $product->purchase_price === '') {
            throw new MissingPurchasePriceException('La vente est refusée : le prix d’achat de cet article n’est pas renseigné.');
        }

        return DB::transaction(function () use ($user, $product, $quantity): Sale {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($lockedProduct->purchase_price === null || $lockedProduct->purchase_price === '') {
                throw new MissingPurchasePriceException('La vente est refusée : le prix d’achat de cet article n’est pas renseigné.');
            }

            $unitSale = Money::normalize($lockedProduct->sale_price);
            $unitCost = Money::normalize($lockedProduct->purchase_price);
            $lineTotal = Money::mul($unitSale, $quantity);
            $costTotal = Money::mul($unitCost, $quantity);
            $profit = Money::sub($lineTotal, $costTotal);
            $boutique = $this->locations->boutique($user->organization);

            $sale = Sale::query()->create([
                'organization_id' => $user->organization_id,
                'reference' => 'TMP-'.Str::uuid(),
                'product_id' => $lockedProduct->id,
                'location_id' => $boutique->id,
                'seller_id' => $user->id,
                'quantity' => $quantity,
                'unit_sale_price' => $unitSale,
                'unit_purchase_cost' => $unitCost,
                'line_total' => $lineTotal,
                'cost_total' => $costTotal,
                'profit' => $profit,
                'status' => SaleStatus::Completed,
                'sold_at' => now(),
            ]);

            $sale->reference = ReferenceCode::make('VTE', $sale->id);
            $sale->save();

            $this->inventory->consumeForSale($user, $lockedProduct, $quantity, Sale::class, $sale->id);
            $this->cash->inflow($user, $lineTotal, $sale, 'Encaissement '.$sale->reference);

            $this->audit->record($user, 'sale.created', $sale, null, [
                'reference' => $sale->reference,
                'product_id' => $lockedProduct->id,
                'quantity' => $quantity,
                'unit_sale_price' => $unitSale,
                'unit_purchase_cost' => $unitCost,
                'line_total' => $lineTotal,
                'cost_total' => $costTotal,
                'profit' => $profit,
                'seller_id' => $user->id,
                'location_id' => $boutique->id,
            ]);

            return $sale->fresh(['product', 'seller', 'location']);
        });
    }

    public function cancel(User $user, Sale $sale, string $reason): Sale
    {
        if (! $user->hasPermission(Permission::CancelSales)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        if ($user->organization_id !== $sale->organization_id) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour annuler une vente.');
        }

        return DB::transaction(function () use ($user, $sale, $reason): Sale {
            /** @var Sale $locked */
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                throw new SaleAlreadyCancelledException('Cette vente est déjà annulée.');
            }

            $locked->load('product');

            $this->inventory->restoreCancelledSale($user, $locked->product, $locked->quantity, $locked->id, $reason);
            $this->cash->outflow($user, Money::normalize($locked->line_total), $locked, 'Annulation '.$locked->reference);

            $locked->forceFill([
                'status' => SaleStatus::Cancelled,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save();

            $this->audit->record($user, 'sale.cancelled', $locked, [
                'status' => SaleStatus::Completed->value,
            ], [
                'status' => SaleStatus::Cancelled->value,
                'reference' => $locked->reference,
                'quantity' => $locked->quantity,
                'line_total' => Money::normalize($locked->line_total),
            ], $reason);

            return $locked->fresh(['product', 'seller', 'location', 'canceller']);
        });
    }
}
