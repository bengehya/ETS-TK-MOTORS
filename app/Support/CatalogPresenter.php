<?php

namespace App\Support;

use App\Enums\LocationType;
use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

class CatalogPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function product(Product $product, bool $withStock = true): array
    {
        $payload = [
            'id' => $product->id,
            'code' => $product->code,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'category' => $product->category,
            'description' => $product->description,
            'sale_price' => $product->sale_price,
            'is_active' => $product->is_active,
        ];

        if ($withStock) {
            $inventories = $product->relationLoaded('inventories')
                ? $product->inventories
                : $product->inventories()->with('location')->get();

            $payload['stocks'] = self::stocks($inventories);
        }

        return $payload;
    }

    /**
     * @param  Collection<int, Inventory>  $inventories
     * @return array{boutique: array<string, mixed>|null, depot: array<string, mixed>|null, sellable_quantity: int, depot_quantity: int}
     */
    public static function stocks(Collection $inventories): array
    {
        $boutique = $inventories->first(fn (Inventory $inventory) => $inventory->location?->type === LocationType::Boutique);
        $depot = $inventories->first(fn (Inventory $inventory) => $inventory->location?->type === LocationType::Depot);

        return [
            'boutique' => $boutique ? self::inventory($boutique) : null,
            'depot' => $depot ? self::inventory($depot) : null,
            'sellable_quantity' => $boutique?->quantity ?? 0,
            'depot_quantity' => $depot?->quantity ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function inventory(Inventory $inventory): array
    {
        $location = $inventory->location;

        return [
            'id' => $inventory->id,
            'quantity' => $inventory->quantity,
            'location' => $location ? self::location($location) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function location(Location $location): array
    {
        return [
            'id' => $location->id,
            'type' => $location->type->value,
            'name' => $location->name,
            'is_sellable' => $location->isSellable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function movement(StockMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'type' => $movement->type->value,
            'type_label' => $movement->type->label(),
            'quantity' => $movement->quantity,
            'quantity_before' => $movement->quantity_before,
            'quantity_after' => $movement->quantity_after,
            'notes' => $movement->notes,
            'transfer_group_id' => $movement->transfer_group_id,
            'created_at' => $movement->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'product' => $movement->relationLoaded('product') && $movement->product ? [
                'id' => $movement->product->id,
                'code' => $movement->product->code,
                'name' => $movement->product->name,
            ] : null,
            'location' => $movement->relationLoaded('location') && $movement->location
                ? self::location($movement->location)
                : null,
            'user' => $movement->relationLoaded('user') && $movement->user ? [
                'id' => $movement->user->id,
                'name' => $movement->user->name,
            ] : null,
        ];
    }
}
