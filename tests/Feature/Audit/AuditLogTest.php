<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\LocationProvisioner;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_operations_are_written_to_the_audit_log(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'sale_price' => '15.00',
            'purchase_price' => '5.00',
        ]);
        $boutique = app(LocationProvisioner::class)->boutique($boss->organization);

        app(InventoryService::class)->receive($boss, $product, $boutique, 4, 'Entrée');
        $sale = app(SaleService::class)->sell($boss, $product, 1);

        $this->actingAs($boss)->put(route('products.update', $product), [
            'code' => $product->code,
            'name' => $product->name,
            'category' => $product->category,
            'sale_price' => '18.00',
            'purchase_price' => '5.00',
        ])->assertRedirect();

        $this->actingAs($boss)
            ->post(route('sales.cancel', $sale), ['reason' => 'Erreur de caisse'])
            ->assertRedirect();

        $actions = AuditLog::query()->pluck('action');

        $this->assertTrue($actions->contains('stock.receipt'));
        $this->assertTrue($actions->contains('sale.created'));
        $this->assertTrue($actions->contains('cash.movement'));
        $this->assertTrue($actions->contains('product.price_changed'));
        $this->assertTrue($actions->contains('sale.cancelled'));
        $this->assertTrue($actions->contains('stock.sale_return'));

        $price = AuditLog::query()->where('action', 'product.price_changed')->firstOrFail();
        $this->assertSame($boss->id, $price->user_id);
        $this->assertSame('15.00', $price->old_values['sale_price']);
        $this->assertSame('18.00', $price->new_values['sale_price']);

        $this->actingAs($boss)
            ->get(route('audit.index', ['action' => 'sale.created']))
            ->assertOk()
            ->assertSee('sale.created', false);

        $employee = User::factory()->employe()->create(['organization_id' => $boss->organization_id]);
        $this->actingAs($employee)->get(route('audit.index'))->assertForbidden();
    }
}
