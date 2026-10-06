<?php

namespace App\Services;

use App\Enums\AdjustmentMotif;
use App\Enums\StockMovementType;
use App\Exceptions\InactiveProductException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\UnsellableLocationException;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InventoryService
{
    public function initializeForProduct(Product $product): void
    {
        $locations = Location::query()
            ->where('organization_id', $product->organization_id)
            ->get();

        foreach ($locations as $location) {
            Inventory::query()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                ],
                [
                    'organization_id' => $product->organization_id,
                    'quantity' => 0,
                ],
            );
        }
    }

    public function receive(
        User $user,
        Product $product,
        Location $location,
        int $quantity,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        $this->assertSameOrganization($user, $product, $location);
        $this->assertPositiveQuantity($quantity);
        $this->assertActiveProduct($product);

        return DB::transaction(function () use ($user, $product, $location, $quantity, $notes, $referenceType, $referenceId): StockMovement {
            $inventory = $this->lockInventory($product, $location);

            $movement = $this->applyIncrease(
                $inventory,
                $user,
                StockMovementType::Receipt,
                $quantity,
                $notes,
                null,
                $referenceType,
                $referenceId,
            );

            app(AuditLogger::class)->record($user, 'stock.receipt', $movement, null, [
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity' => $quantity,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
            ]);

            return $movement;
        });
    }

    /**
     * Transfert réel Dépôt → Boutique. Interdit de simuler cela par une édition manuelle de quantité.
     *
     * @return array{out: StockMovement, in: StockMovement}
     */
    public function transferDepotToBoutique(User $user, Product $product, int $quantity, ?string $notes = null): array
    {
        $this->assertPositiveQuantity($quantity);
        $this->assertActiveProduct($product);

        $provisioner = app(LocationProvisioner::class);
        $depot = $provisioner->depot($user->organization);
        $boutique = $provisioner->boutique($user->organization);

        $this->assertSameOrganization($user, $product, $depot);
        $this->assertSameOrganization($user, $product, $boutique);

        return DB::transaction(function () use ($user, $product, $depot, $boutique, $quantity, $notes): array {
            $source = $this->lockInventory($product, $depot);
            $destination = $this->lockInventory($product, $boutique);

            if ($source->quantity < $quantity) {
                throw new InsufficientStockException('Stock insuffisant au dépôt pour ce transfert.');
            }

            $groupId = (string) Str::uuid();

            $out = $this->applyDecrease(
                $source,
                $user,
                StockMovementType::TransferOut,
                $quantity,
                $notes,
                $groupId,
            );

            $in = $this->applyIncrease(
                $destination,
                $user,
                StockMovementType::TransferIn,
                $quantity,
                $notes,
                $groupId,
            );

            app(AuditLogger::class)->record($user, 'stock.transfer', $in, [
                'depot_quantity_before' => $out->quantity_before,
                'boutique_quantity_before' => $in->quantity_before,
            ], [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'depot_quantity_after' => $out->quantity_after,
                'boutique_quantity_after' => $in->quantity_after,
            ]);

            return ['out' => $out, 'in' => $in];
        });
    }

    /**
     * Correction exceptionnelle de stock : perte, casse, erreur de comptage,
     * différence d'inventaire, pièce retrouvée, erreur de saisie ou autre écart.
     *
     * Ne jamais utiliser cet ajustement pour annuler une vente. Une annulation
     * devra, dans le module Ventes, annuler la vente, restaurer le stock,
     * traiter l'argent, conserver l'historique et enregistrer l'utilisateur.
     */
    public function adjust(
        User $user,
        Product $product,
        Location $location,
        int $quantity,
        string $direction,
        string $reason,
        AdjustmentMotif $motif,
    ): StockMovement {
        $this->assertSameOrganization($user, $product, $location);
        $this->assertPositiveQuantity($quantity);

        $reason = trim($reason);

        if (! in_array($direction, ['increase', 'decrease'], true)) {
            throw new InvalidArgumentException('Direction d’ajustement invalide.');
        }

        $notes = $motif->label();

        if ($reason !== '') {
            $notes .= ' — '.$reason;
        }

        return DB::transaction(function () use ($user, $product, $location, $quantity, $direction, $notes, $motif, $reason): StockMovement {
            $inventory = $this->lockInventory($product, $location);

            $movement = $direction === 'increase'
                ? $this->applyIncrease(
                    $inventory,
                    $user,
                    StockMovementType::Adjustment,
                    $quantity,
                    $notes,
                    null,
                    null,
                    null,
                    $direction,
                    $motif,
                )
                : $this->applyDecrease(
                    $inventory,
                    $user,
                    StockMovementType::Adjustment,
                    $quantity,
                    $notes,
                    null,
                    null,
                    null,
                    $direction,
                    $motif,
                );

            app(AuditLogger::class)->record($user, 'stock.adjustment', $movement, [
                'quantity_before' => $movement->quantity_before,
            ], [
                'product_id' => $product->id,
                'location_id' => $location->id,
                'direction' => $direction,
                'quantity' => $quantity,
                'quantity_after' => $movement->quantity_after,
                'motif' => $motif->value,
            ], $reason);

            return $movement;
        });
    }

    /**
     * Sortie de stock liée à une vente. Utilise exclusivement la boutique.
     * Le dépôt n’est jamais disponible à la vente.
     *
     * Le vendeur est l'utilisateur authentifié passé ici. Le patron et l'employé
     * peuvent vendre ; l'identité du vendeur doit rester enregistrée sur la vente.
     * Cette sortie ne remplace pas l'annulation d'une vente.
     */
    public function consumeForSale(
        User $user,
        Product $product,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        $this->assertPositiveQuantity($quantity);
        $this->assertActiveProduct($product);

        $boutique = app(LocationProvisioner::class)->boutique($user->organization);
        $this->assertSameOrganization($user, $product, $boutique);

        if (! $boutique->isSellable()) {
            throw new UnsellableLocationException('Le stock du dépôt n’est pas vendable en boutique.');
        }

        return DB::transaction(function () use ($user, $product, $boutique, $quantity, $referenceType, $referenceId): StockMovement {
            $inventory = $this->lockInventory($product, $boutique);

            $movement = $this->applyDecrease(
                $inventory,
                $user,
                StockMovementType::Sale,
                $quantity,
                'Sortie liée à une vente',
                null,
                $referenceType,
                $referenceId,
            );

            app(AuditLogger::class)->record($user, 'stock.sale', $movement, null, [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
            ]);

            return $movement;
        });
    }

    /**
     * Restaure exactement la quantité vendue. Ce n'est pas un ajustement exceptionnel.
     */
    public function restoreCancelledSale(
        User $user,
        Product $product,
        int $quantity,
        int $saleId,
        string $reason,
    ): StockMovement {
        $this->assertPositiveQuantity($quantity);

        $boutique = app(LocationProvisioner::class)->boutique($user->organization);
        $this->assertSameOrganization($user, $product, $boutique);

        return DB::transaction(function () use ($user, $product, $boutique, $quantity, $saleId, $reason): StockMovement {
            $inventory = $this->lockInventory($product, $boutique);

            $movement = $this->applyIncrease(
                $inventory,
                $user,
                StockMovementType::SaleReturn,
                $quantity,
                'Restauration après annulation de vente — '.$reason,
                null,
                Sale::class,
                $saleId,
            );

            app(AuditLogger::class)->record($user, 'stock.sale_return', $movement, null, [
                'sale_id' => $saleId,
                'quantity' => $quantity,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
            ], $reason);

            return $movement;
        });
    }

    public function consumeFromLocationForSale(User $user, Product $product, Location $location, int $quantity): StockMovement
    {
        if (! $location->isSellable()) {
            throw new UnsellableLocationException('Le stock de cet emplacement n’est pas disponible à la vente.');
        }

        $this->assertSameOrganization($user, $product, $location);
        $this->assertPositiveQuantity($quantity);
        $this->assertActiveProduct($product);

        return DB::transaction(function () use ($user, $product, $location, $quantity): StockMovement {
            $inventory = $this->lockInventory($product, $location);

            return $this->applyDecrease(
                $inventory,
                $user,
                StockMovementType::Sale,
                $quantity,
                'Sortie liée à une vente',
            );
        });
    }

    private function applyIncrease(
        Inventory $inventory,
        User $user,
        StockMovementType $type,
        int $quantity,
        ?string $notes = null,
        ?string $transferGroupId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $direction = null,
        ?AdjustmentMotif $motif = null,
    ): StockMovement {
        $before = $inventory->quantity;
        $after = $before + $quantity;

        $inventory->quantity = $after;
        $inventory->save();

        return $this->recordMovement(
            $inventory,
            $user,
            $type,
            $quantity,
            $before,
            $after,
            $notes,
            $transferGroupId,
            $referenceType,
            $referenceId,
            $direction,
            $motif,
        );
    }

    private function applyDecrease(
        Inventory $inventory,
        User $user,
        StockMovementType $type,
        int $quantity,
        ?string $notes = null,
        ?string $transferGroupId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $direction = null,
        ?AdjustmentMotif $motif = null,
    ): StockMovement {
        $before = $inventory->quantity;

        if ($before < $quantity) {
            throw new InsufficientStockException('Stock insuffisant : une quantité négative n’est pas autorisée.');
        }

        $after = $before - $quantity;

        $inventory->quantity = $after;
        $inventory->save();

        return $this->recordMovement(
            $inventory,
            $user,
            $type,
            $quantity,
            $before,
            $after,
            $notes,
            $transferGroupId,
            $referenceType,
            $referenceId,
            $direction,
            $motif,
        );
    }

    private function recordMovement(
        Inventory $inventory,
        User $user,
        StockMovementType $type,
        int $quantity,
        int $before,
        int $after,
        ?string $notes,
        ?string $transferGroupId,
        ?string $referenceType,
        ?int $referenceId,
        ?string $direction = null,
        ?AdjustmentMotif $motif = null,
    ): StockMovement {
        return StockMovement::query()->create([
            'organization_id' => $inventory->organization_id,
            'product_id' => $inventory->product_id,
            'location_id' => $inventory->location_id,
            'type' => $type,
            'direction' => $direction,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'user_id' => $user->id,
            'transfer_group_id' => $transferGroupId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'adjustment_motif' => $motif,
        ]);
    }

    private function lockInventory(Product $product, Location $location): Inventory
    {
        $this->initializeForProduct($product);

        /** @var Inventory $inventory */
        $inventory = Inventory::query()
            ->where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->lockForUpdate()
            ->firstOrFail();

        return $inventory;
    }

    private function assertSameOrganization(User $user, Product $product, Location $location): void
    {
        if (
            $user->organization_id !== $product->organization_id
            || $user->organization_id !== $location->organization_id
        ) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('La quantité doit être un entier positif.');
        }
    }

    private function assertActiveProduct(Product $product): void
    {
        if (! $product->is_active) {
            throw new InactiveProductException('Cet article est désactivé.');
        }
    }
}
