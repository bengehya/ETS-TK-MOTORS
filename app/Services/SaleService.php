<?php

namespace App\Services;

use App\Enums\Currency;
use App\Enums\Permission;
use App\Enums\SaleStatus;
use App\Exceptions\DuplicateSaleException;
use App\Exceptions\InactiveProductException;
use App\Exceptions\InsufficientPaymentException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\MissingPurchasePriceException;
use App\Exceptions\SaleAlreadyCancelledException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
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
        return $this->sellCart($user, [
            ['product' => $product, 'quantity' => $quantity],
        ]);
    }

    /**
     * Vend un panier depuis la boutique. Les prix viennent du catalogue verrouillé.
     * Le montant encaissé en caisse est le total de la vente, jamais la monnaie rendue.
     *
     * @param  list<array{product: Product, quantity: int}>  $lines
     */
    public function sellCart(
        User $user,
        array $lines,
        Currency $currency = Currency::Usd,
        ?string $amountReceived = null,
        ?string $clientToken = null,
    ): Sale {
        if (! $user->hasPermission(Permission::CreateSales)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        if ($lines === []) {
            throw new InvalidArgumentException('La vente doit contenir au moins un article.');
        }

        $clientToken = $this->blankToNull($clientToken);

        /** @var array<int, int> $quantities */
        $quantities = [];
        $order = [];

        foreach ($lines as $line) {
            $product = $line['product'];
            $quantity = $line['quantity'];

            if ($user->organization_id !== $product->organization_id) {
                throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
            }

            if ($quantity < 1) {
                throw new InvalidArgumentException('La quantité doit être un entier positif.');
            }

            if (! isset($quantities[$product->id])) {
                $order[] = $product->id;
            }

            $quantities[$product->id] = ($quantities[$product->id] ?? 0) + $quantity;
        }

        return DB::transaction(function () use ($user, $quantities, $order, $currency, $amountReceived, $clientToken): Sale {
            if ($clientToken !== null) {
                $existing = Sale::query()
                    ->where('organization_id', $user->organization_id)
                    ->where('client_token', $clientToken)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    throw new DuplicateSaleException('Cette vente a déjà été enregistrée.');
                }
            }

            $lockIds = array_keys($quantities);
            sort($lockIds);

            /** @var array<int, Product> $lockedProducts */
            $lockedProducts = [];

            foreach ($lockIds as $productId) {
                /** @var Product $lockedProduct */
                $lockedProduct = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
                $lockedProducts[$productId] = $lockedProduct;

                if (! $lockedProduct->is_active) {
                    $exception = new InactiveProductException('Cet article est désactivé.');
                    $exception->productId = $lockedProduct->id;
                    throw $exception;
                }

                if ($lockedProduct->purchase_price === null || $lockedProduct->purchase_price === '') {
                    $exception = new MissingPurchasePriceException('La vente est refusée : le prix d’achat de cet article n’est pas renseigné.');
                    $exception->productId = $lockedProduct->id;
                    throw $exception;
                }
            }

            $prepared = [];
            $position = 1;
            $quantityTotal = 0;
            $lineTotal = '0.00';
            $costTotal = '0.00';
            $profitTotal = '0.00';

            foreach ($order as $productId) {
                $lockedProduct = $lockedProducts[$productId];
                $quantity = $quantities[$productId];
                $unitSale = Money::normalize($lockedProduct->sale_price);
                $unitCost = Money::normalize($lockedProduct->purchase_price);
                $rowTotal = Money::mul($unitSale, $quantity);
                $rowCost = Money::mul($unitCost, $quantity);
                $rowProfit = Money::sub($rowTotal, $rowCost);

                $prepared[] = [
                    'product' => $lockedProduct,
                    'quantity' => $quantity,
                    'unit_sale_price' => $unitSale,
                    'unit_purchase_cost' => $unitCost,
                    'line_total' => $rowTotal,
                    'cost_total' => $rowCost,
                    'profit' => $rowProfit,
                    'position' => $position,
                ];

                $position++;
                $quantityTotal += $quantity;
                $lineTotal = Money::add($lineTotal, $rowTotal);
                $costTotal = Money::add($costTotal, $rowCost);
                $profitTotal = Money::add($profitTotal, $rowProfit);
            }

            $received = $amountReceived === null
                ? $lineTotal
                : Money::normalize($amountReceived);

            if (Money::cmp($received, $lineTotal) < 0) {
                throw new InsufficientPaymentException('Le montant reçu est inférieur au total à payer.');
            }

            $change = Money::sub($received, $lineTotal);
            $boutique = $this->locations->boutique($user->organization);
            $first = $prepared[0];

            $sale = Sale::query()->create([
                'organization_id' => $user->organization_id,
                'reference' => 'TMP-'.Str::uuid(),
                'currency' => $currency,
                'product_id' => $first['product']->id,
                'location_id' => $boutique->id,
                'seller_id' => $user->id,
                'quantity' => $quantityTotal,
                'unit_sale_price' => $first['unit_sale_price'],
                'unit_purchase_cost' => $first['unit_purchase_cost'],
                'line_total' => $lineTotal,
                'cost_total' => $costTotal,
                'profit' => $profitTotal,
                'amount_received' => $received,
                'change_given' => $change,
                'client_token' => $clientToken,
                'status' => SaleStatus::Completed,
                'sold_at' => now(),
            ]);

            $sale->reference = ReferenceCode::make('VTE', $sale->id);
            $sale->save();

            foreach ($prepared as $row) {
                SaleLine::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $row['product']->id,
                    'quantity' => $row['quantity'],
                    'unit_sale_price' => $row['unit_sale_price'],
                    'unit_purchase_cost' => $row['unit_purchase_cost'],
                    'line_total' => $row['line_total'],
                    'cost_total' => $row['cost_total'],
                    'profit' => $row['profit'],
                    'position' => $row['position'],
                ]);
            }

            foreach ($lockIds as $productId) {
                try {
                    $this->inventory->consumeForSale(
                        $user,
                        $lockedProducts[$productId],
                        $quantities[$productId],
                        Sale::class,
                        $sale->id,
                    );
                } catch (InsufficientStockException $exception) {
                    $exception->productId = $productId;
                    throw $exception;
                }
            }

            $this->cash->inflow(
                $user,
                $lineTotal,
                $sale,
                'Encaissement '.$sale->reference,
                $currency,
            );

            $this->audit->record($user, 'sale.created', $sale, null, [
                'reference' => $sale->reference,
                'currency' => $currency->value,
                'quantity' => $quantityTotal,
                'line_total' => $lineTotal,
                'cost_total' => $costTotal,
                'profit' => $profitTotal,
                'amount_received' => $received,
                'change_given' => $change,
                'seller_id' => $user->id,
                'location_id' => $boutique->id,
                'lines' => array_map(fn (array $row): array => [
                    'product_id' => $row['product']->id,
                    'quantity' => $row['quantity'],
                    'unit_sale_price' => $row['unit_sale_price'],
                    'unit_purchase_cost' => $row['unit_purchase_cost'],
                    'line_total' => $row['line_total'],
                ], $prepared),
            ]);

            return $sale->fresh(['product', 'seller', 'location', 'lines.product']);
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

            $locked->load('lines.product', 'product');
            $currency = $locked->currency instanceof Currency
                ? $locked->currency
                : Currency::Usd;

            $restorations = $locked->lines->isNotEmpty()
                ? $locked->lines->map(fn (SaleLine $line): array => [
                    'product' => $line->product,
                    'quantity' => $line->quantity,
                ])->all()
                : [[
                    'product' => $locked->product,
                    'quantity' => $locked->quantity,
                ]];

            foreach ($restorations as $restoration) {
                $this->inventory->restoreCancelledSale(
                    $user,
                    $restoration['product'],
                    $restoration['quantity'],
                    $locked->id,
                    $reason,
                );
            }

            $this->cash->outflow(
                $user,
                Money::normalize($locked->line_total),
                $locked,
                'Annulation '.$locked->reference,
                $currency,
            );

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
                'currency' => $currency->value,
                'quantity' => $locked->quantity,
                'line_total' => Money::normalize($locked->line_total),
            ], $reason);

            return $locked->fresh(['product', 'seller', 'location', 'canceller', 'lines.product']);
        });
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
