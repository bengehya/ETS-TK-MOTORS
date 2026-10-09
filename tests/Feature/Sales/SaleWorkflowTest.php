<?php

namespace Tests\Feature\Sales;

use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\MissingPurchasePriceException;
use App\Models\CashAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\LocationProvisioner;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SaleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_boss_and_an_employee_can_sell_from_the_boutique(): void
    {
        [$boss, $employee, $product] = $this->prepareSale(5);

        $bossSale = app(SaleService::class)->sell($boss, $product, 1);
        $employeeSale = app(SaleService::class)->sell($employee, $product, 2);

        $this->assertSame($boss->id, $bossSale->seller_id);
        $this->assertSame($employee->id, $employeeSale->seller_id);
        $this->assertSame('VTE-'.str_pad((string) $bossSale->id, 6, '0', STR_PAD_LEFT), $bossSale->reference);
        $this->assertSame(SaleStatus::Completed, $bossSale->status);
        $this->assertSame('19.00', $bossSale->unit_sale_price);
        $this->assertSame('7.13', $bossSale->unit_purchase_cost);
        $this->assertSame('38.00', $employeeSale->line_total);
        $this->assertSame('14.26', $employeeSale->cost_total);
        $this->assertSame('23.74', $employeeSale->profit);
        $this->assertNotNull($employeeSale->sold_at);
        $this->assertSame(2, $this->quantity($boss, $product, 'boutique'));
        $this->assertSame('57.00', CashAccount::query()->where('organization_id', $boss->organization_id)->value('balance'));
    }

    public function test_a_sale_keeps_price_snapshots_when_the_catalog_changes(): void
    {
        [$boss, $employee, $product] = $this->prepareSale(4);
        $sale = app(SaleService::class)->sell($employee, $product, 1);

        $product->update([
            'sale_price' => '40.00',
            'purchase_price' => '3.00',
        ]);

        $sale->refresh();

        $this->assertSame('19.00', $sale->unit_sale_price);
        $this->assertSame('7.13', $sale->unit_purchase_cost);
        $this->assertSame('11.87', $sale->profit);
    }

    public function test_the_depot_cannot_be_sold_and_stock_never_goes_negative(): void
    {
        [$boss, $employee, $product] = $this->prepareSale(0);
        $depot = app(LocationProvisioner::class)->depot($boss->organization);
        app(InventoryService::class)->receive($boss, $product, $depot, 4, 'Dépôt seul');

        $this->expectException(InsufficientStockException::class);

        try {
            app(SaleService::class)->sell($employee, $product, 1);
        } finally {
            $this->assertSame(0, Sale::query()->count());
            $this->assertSame(0, $this->quantity($boss, $product, 'boutique'));
            $this->assertSame(4, $this->quantity($boss, $product, 'depot'));
            $this->assertNull(CashAccount::query()->where('organization_id', $boss->organization_id)->first());
        }
    }

    public function test_a_sale_is_refused_without_a_purchase_price(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'sale_price' => '19.00',
            'purchase_price' => null,
        ]);
        app(InventoryService::class)->receive(
            $boss,
            $product,
            app(LocationProvisioner::class)->boutique($boss->organization),
            3,
            'Stock',
        );

        $this->expectException(MissingPurchasePriceException::class);

        try {
            app(SaleService::class)->sell($boss, $product, 1);
        } finally {
            $this->assertSame(0, Sale::query()->count());
            $this->assertSame(3, $this->quantity($boss, $product, 'boutique'));
        }
    }

    public function test_two_sales_cannot_consume_more_than_the_boutique_stock(): void
    {
        [$boss, $employee, $product] = $this->prepareSale(1);

        app(SaleService::class)->sell($boss, $product, 1);

        try {
            app(SaleService::class)->sell($employee, $product, 1);
            $this->fail('La seconde vente aurait dû être refusée.');
        } catch (InsufficientStockException) {
            $this->assertSame(1, Sale::query()->count());
            $this->assertSame(0, $this->quantity($boss, $product, 'boutique'));
        }
    }

    public function test_http_sale_ignores_a_client_supplied_price_and_hides_cost_from_an_employee(): void
    {
        [$boss, $employee, $product] = $this->prepareSale(3);

        $this->actingAs($employee)
            ->post(route('sales.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_sale_price' => '1.00',
            ])
            ->assertRedirect();

        $sale = Sale::query()->firstOrFail();
        $this->assertSame('19.00', $sale->unit_sale_price);
        $this->assertSame($employee->id, $sale->seller_id);

        $this->actingAs($employee)
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sale.reference', $sale->reference)
                ->where('sale.quantity', 1)
                ->where('sale.unit_sale_price', '19.00')
                ->where('sale.line_total', '19.00')
                ->where('sale.seller.id', $employee->id)
                ->missing('sale.unit_purchase_cost')
                ->missing('sale.cost_total')
                ->missing('sale.profit')
                ->where('canCancel', false)
            );

        $this->assertStringNotContainsString('7.13', $this->actingAs($employee)->get(route('sales.show', $sale))->getContent());
        $this->assertStringNotContainsString('11.87', $this->actingAs($employee)->get(route('sales.show', $sale))->getContent());

        $this->actingAs($boss)
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sale.unit_purchase_cost', '7.13')
                ->where('sale.profit', '11.87')
                ->where('canCancel', true)
            );
    }

    public function test_search_matches_name_code_and_barcode_without_exposing_cost_to_an_employee(): void
    {
        [$boss, $employee, $product] = $this->prepareSale(2);
        $product->update(['barcode' => 'EAN-7788', 'code' => 'FRN-19']);

        $this->actingAs($employee)
            ->get(route('sales.search', ['q' => 'EAN-7788']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('matches.0.code', 'FRN-19')
                ->where('matches.0.sale_price', '19.00')
                ->missing('matches.0.purchase_price')
            );

        $this->actingAs($boss)
            ->get(route('sales.search', ['q' => 'plaquette']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('matches.0.purchase_price', '7.13')
            );
    }

    public function test_history_can_be_filtered_and_shows_who_sold(): void
    {
        [$boss, $employee, $product] = $this->prepareSale(4);
        app(SaleService::class)->sell($boss, $product, 1);
        $employeeSale = app(SaleService::class)->sell($employee, $product, 1);

        $this->actingAs($employee)
            ->get(route('sales.index', ['seller_id' => $employee->id, 'q' => $product->name]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sales.data', 1)
                ->where('sales.data.0.id', $employeeSale->id)
                ->where('sales.data.0.seller.id', $employee->id)
                ->missing('sales.data.0.profit')
            );
    }

    public function test_only_a_boss_can_cancel_and_the_cancellation_is_traced(): void
    {
        [$boss, $employee, $product] = $this->prepareSale(3);
        $sale = app(SaleService::class)->sell($employee, $product, 2);

        $this->actingAs($employee)
            ->post(route('sales.cancel', $sale), ['reason' => 'Erreur'])
            ->assertForbidden();

        $this->assertSame(SaleStatus::Completed, $sale->refresh()->status);
        $this->assertSame(1, $this->quantity($boss, $product, 'boutique'));

        $this->actingAs($boss)
            ->post(route('sales.cancel', $sale), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($boss)
            ->post(route('sales.cancel', $sale), ['reason' => 'Client revenu'])
            ->assertRedirect(route('sales.show', $sale));

        $sale->refresh();
        $this->assertSame(SaleStatus::Cancelled, $sale->status);
        $this->assertSame($boss->id, $sale->cancelled_by);
        $this->assertSame('Client revenu', $sale->cancellation_reason);
        $this->assertNotNull($sale->cancelled_at);
        $this->assertSame(3, $this->quantity($boss, $product, 'boutique'));
        $this->assertSame('0.00', CashAccount::query()->where('organization_id', $boss->organization_id)->value('balance'));
        $this->assertTrue(StockMovement::query()
            ->where('type', StockMovementType::SaleReturn)
            ->where('reference_id', $sale->id)
            ->where('quantity', 2)
            ->exists());
        $this->assertSame(1, Sale::query()->count());

        $this->actingAs($boss)
            ->post(route('sales.cancel', $sale), ['reason' => 'Encore'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($boss)
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sale.cancellation_reason', 'Client revenu')
                ->where('sale.canceller.id', $boss->id)
                ->where('canCancel', false)
            );
    }

    public function test_guests_and_other_organizations_cannot_reach_sales(): void
    {
        [$boss, $employee, $product] = $this->prepareSale(2);
        $sale = app(SaleService::class)->sell($boss, $product, 1);
        $outsider = User::factory()->bossPrincipal()->create();

        $this->get(route('sales.index'))->assertRedirect(route('login'));
        $this->actingAs($outsider)->get(route('sales.show', $sale))->assertNotFound();
        $this->actingAs($employee)->get(route('cash.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('audit.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('savings.show'))->assertForbidden();
        $this->actingAs($employee)->get(route('rentals.index'))->assertForbidden();
    }

    /**
     * @return array{0: User, 1: User, 2: Product}
     */
    private function prepareSale(int $boutiqueQuantity): array
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create([
            'organization_id' => $boss->organization_id,
        ]);
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'name' => 'Plaquette frein',
            'sale_price' => '19.00',
            'purchase_price' => '7.13',
        ]);

        if ($boutiqueQuantity > 0) {
            app(InventoryService::class)->receive(
                $boss,
                $product,
                app(LocationProvisioner::class)->boutique($boss->organization),
                $boutiqueQuantity,
                'Préparation',
            );
        }

        return [$boss, $employee, $product];
    }

    private function quantity(User $user, Product $product, string $place): int
    {
        $locations = app(LocationProvisioner::class);
        $location = $place === 'boutique'
            ? $locations->boutique($user->organization)
            : $locations->depot($user->organization);

        return (int) $product->inventories()->where('location_id', $location->id)->value('quantity');
    }
}
