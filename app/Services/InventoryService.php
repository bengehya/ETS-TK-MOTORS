<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InactiveProductException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\UnsellableLocationException;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
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

    public function receive(User $user, Product $product, Location $location, int $quantity, ?string $notes = null): StockMovement
    {
        $this->assertSameOrganization($user, $product, $location);
        $this->assertPositiveQuantity($quantity);
        $this->assertActiveProduct($product);

        return DB::transaction(function () use ($user, $product, $location, $quantity, $notes): StockMovement {
            $inventory = $this->lockInventory($product, $location);

            return $this->applyIncrease(
                $inventory,
                $user,
                StockMovementType::Receipt,
                $quantity,
                $notes,
            );
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

            return ['out' => $out, 'in' => $in];
        });
    }

    public function adjust(User $user, Product $product, Location $location, int $quantity, string $direction, string $reason): StockMovement
    {
        $this->assertSameOrganization($user, $product, $location);
        $this->assertPositiveQuantity($quantity);

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour un ajustement.');
        }

        if (! in_array($direction, ['increase', 'decrease'], true)) {
            throw new InvalidArgumentException('Direction d’ajustement invalide.');
        }

        return DB::transaction(function () use ($user, $product, $location, $quantity, $direction, $reason): StockMovement {
            $inventory = $this->lockInventory($product, $location);

            if ($direction === 'increase') {
                return $this->applyIncrease(
                    $inventory,
                    $user,
                    StockMovementType::Adjustment,
                    $quantity,
                    $reason,
                );
            }

            return $this->applyDecrease(
                $inventory,
                $user,
                StockMovementType::Adjustment,
                $quantity,
                $reason,
            );
        });
    }

    /**
     * Sortie de stock liée à une vente. Utilise exclusivement la boutique.
     * Le dépôt n’est jamais disponible à la vente.
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

            return $this->applyDecrease(
                $inventory,
                $user,
                StockMovementType::Sale,
                $quantity,
                'Sortie liée à une vente',
                null,
                $referenceType,
                $referenceId,
            );
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
    ): StockMovement {
        return StockMovement::query()->create([
            'organization_id' => $inventory->organization_id,
            'product_id' => $inventory->product_id,
            'location_id' => $inventory->location_id,
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'user_id' => $user->id,
            'transfer_group_id' => $transferGroupId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
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
