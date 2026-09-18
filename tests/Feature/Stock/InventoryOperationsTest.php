<?php

namespace Tests\Feature\Stock;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\UnsellableLocationException;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\LocationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_boutique_and_depot_stocks_are_independent(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create(['organization_id' => $boss->organization_id]);
        $locations = app(LocationProvisioner::class);
        $depot = $locations->depot($boss->organization);
        $boutique = $locations->boutique($boss->organization);

        $this->actingAs($boss)->post('/articles/'.$product->id.'/stock/entree', [
            'location_id' => $depot->id,
            'quantity' => 8,
            'notes' => 'Arrivage dépôt',
        ])->assertRedirect();

        $this->assertSame(8, $this->quantity($product, $depot));
        $this->assertSame(0, $this->quantity($product, $boutique));
        $this->assertFalse($depot->isSellable());
        $this->assertTrue($boutique->isSellable());
    }

    public function test_depot_stock_cannot_be_sold_and_boutique_cannot_go_negative(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create(['organization_id' => $boss->organization_id]);
        $locations = app(LocationProvisioner::class);
        $depot = $locations->depot($boss->organization);
        $boutique = $locations->boutique($boss->organization);
        $service = app(InventoryService::class);

        $service->receive($boss, $product, $depot, 5, 'Stock dépôt');

        $this->expectException(UnsellableLocationException::class);
        $service->consumeFromLocationForSale($boss, $product, $depot, 1);
    }

    public function test_selling_missing_boutique_stock_is_rejected_and_does_not_use_depot(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create(['organization_id' => $boss->organization_id]);
        $locations = app(LocationProvisioner::class);
        $depot = $locations->depot($boss->organization);
        $service = app(InventoryService::class);

        $service->receive($boss, $product, $depot, 10, 'Stock dépôt');

        try {
            $service->consumeForSale($boss, $product, 1);
            $this->fail('A missing boutique stock should not be sold.');
        } catch (InsufficientStockException) {
            $this->assertSame(10, $this->quantity($product, $depot));
            $this->assertSame(0, $this->quantity($product, $locations->boutique($boss->organization)));
        }
    }

    public function test_a_real_transfer_moves_stock_from_depot_to_boutique(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create(['organization_id' => $boss->organization_id]);
        $locations = app(LocationProvisioner::class);
        $depot = $locations->depot($boss->organization);
        $boutique = $locations->boutique($boss->organization);

        app(InventoryService::class)->receive($boss, $product, $depot, 6, 'Réception');

        $this->actingAs($boss)->post('/articles/'.$product->id.'/stock/transfert', [
            'quantity' => 4,
            'notes' => 'Réassort boutique',
        ])->assertRedirect();

        $this->assertSame(2, $this->quantity($product, $depot));
        $this->assertSame(4, $this->quantity($product, $boutique));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'location_id' => $depot->id,
            'type' => StockMovementType::TransferOut->value,
            'quantity' => 4,
            'user_id' => $boss->id,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'location_id' => $boutique->id,
            'type' => StockMovementType::TransferIn->value,
            'quantity' => 4,
            'user_id' => $boss->id,
        ]);

        $groupIds = StockMovement::query()
            ->where('product_id', $product->id)
            ->whereIn('type', [StockMovementType::TransferOut->value, StockMovementType::TransferIn->value])
            ->pluck('transfer_group_id');

        $this->assertCount(2, $groupIds);
        $this->assertNotNull($groupIds->first());
        $this->assertTrue($groupIds->every(fn ($id) => $id === $groupIds->first()));
    }

    public function test_a_failed_transfer_does_not_leave_partial_movements(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create(['organization_id' => $boss->organization_id]);

        $this->actingAs($boss)->post('/articles/'.$product->id.'/stock/transfert', [
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(0, (int) Inventory::query()->where('product_id', $product->id)->sum('quantity'));
    }

    public function test_an_inactive_article_cannot_receive_or_transfer_stock(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->inactive()->create([
            'organization_id' => $boss->organization_id,
        ]);
        $depot = app(LocationProvisioner::class)->depot($boss->organization);

        $this->actingAs($boss)->post('/articles/'.$product->id.'/stock/entree', [
            'location_id' => $depot->id,
            'quantity' => 2,
        ])->assertSessionHasErrors('quantity');

        $this->actingAs($boss)->post('/articles/'.$product->id.'/stock/transfert', [
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_an_adjustment_requires_a_controlled_reason(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create(['organization_id' => $boss->organization_id]);
        $boutique = app(LocationProvisioner::class)->boutique($boss->organization);

        $this->actingAs($boss)->post('/articles/'.$product->id.'/stock/ajustement', [
            'location_id' => $boutique->id,
            'direction' => 'increase',
            'quantity' => 1,
            'reason' => 'court',
        ])->assertSessionHasErrors('reason');

        $this->actingAs($boss)->post('/articles/'.$product->id.'/stock/ajustement', [
            'location_id' => $boutique->id,
            'direction' => 'increase',
            'quantity' => 1,
            'reason' => 'Inventaire physique boutique',
        ])->assertRedirect();

        $this->assertSame(1, $this->quantity($product, $boutique));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Adjustment->value,
            'user_id' => $boss->id,
            'notes' => 'Inventaire physique boutique',
        ]);
    }

    public function test_stock_cannot_become_negative_via_adjustment_or_transfer(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create(['organization_id' => $boss->organization_id]);
        $boutique = app(LocationProvisioner::class)->boutique($boss->organization);

        $this->actingAs($boss)->post('/articles/'.$product->id.'/stock/ajustement', [
            'location_id' => $boutique->id,
            'direction' => 'decrease',
            'quantity' => 1,
            'reason' => 'Correction inventaire boutique',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(0, $this->quantity($product, $boutique));

        $this->actingAs($boss)->post('/articles/'.$product->id.'/stock/transfert', [
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');
    }

    public function test_an_employee_cannot_mutate_stock_but_can_consult_it(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create([
            'organization_id' => $boss->organization_id,
        ]);
        $product = Product::factory()->create(['organization_id' => $boss->organization_id]);
        $depot = app(LocationProvisioner::class)->depot($boss->organization);

        $this->actingAs($employee)->post('/articles/'.$product->id.'/stock/entree', [
            'location_id' => $depot->id,
            'quantity' => 3,
        ])->assertForbidden();

        $this->actingAs($employee)->get('/stocks/boutique')->assertOk();
        $this->actingAs($employee)->get('/stocks/depot')->assertOk();
        $this->actingAs($employee)->get('/stocks/mouvements')->assertOk();
    }

    public function test_stock_movements_are_traced_to_the_responsible_user(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create(['organization_id' => $boss->organization_id]);
        $boutique = app(LocationProvisioner::class)->boutique($boss->organization);

        $this->actingAs($boss)->post('/articles/'.$product->id.'/stock/entree', [
            'location_id' => $boutique->id,
            'quantity' => 2,
            'notes' => 'Réception boutique',
        ])->assertRedirect();

        $movement = StockMovement::query()->first();

        $this->assertNotNull($movement);
        $this->assertSame($boss->id, $movement->user_id);
        $this->assertSame(StockMovementType::Receipt, $movement->type);
        $this->assertSame(0, $movement->quantity_before);
        $this->assertSame(2, $movement->quantity_after);

        $this->actingAs($boss)
            ->get('/stocks/mouvements')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Stock/Movements')
                ->has('movements.data', 1)
                ->where('movements.data.0.type_label', 'Entrée de stock')
                ->where('movements.data.0.user.name', $boss->name)
                ->where('movements.data.0.user.id', $boss->id)
            );
    }

    public function test_sale_consumption_decrements_only_boutique_stock(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create(['organization_id' => $boss->organization_id]);
        $locations = app(LocationProvisioner::class);
        $service = app(InventoryService::class);

        $service->receive($boss, $product, $locations->depot($boss->organization), 5, 'Dépôt');
        $service->receive($boss, $product, $locations->boutique($boss->organization), 3, 'Boutique');
        $service->consumeForSale($boss, $product, 2);

        $this->assertSame(1, $this->quantity($product, $locations->boutique($boss->organization)));
        $this->assertSame(5, $this->quantity($product, $locations->depot($boss->organization)));
        $this->assertDatabaseHas('stock_movements', [
            'type' => StockMovementType::Sale->value,
            'quantity' => 2,
            'user_id' => $boss->id,
        ]);
    }

    private function quantity(Product $product, Location $location): int
    {
        return (int) Inventory::query()
            ->where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->value('quantity');
    }
}
