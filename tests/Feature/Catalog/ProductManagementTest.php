<?php

namespace Tests\Feature\Catalog;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_boss_can_create_an_article_with_a_unique_code(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)->post('/articles', [
            'code' => 'flt-001',
            'name' => 'Filtre à huile',
            'category' => 'Pièces moteur',
            'description' => 'Filtre universel',
            'sale_price' => '15.50',
        ])->assertRedirect();

        $product = Product::query()->first();

        $this->assertNotNull($product);
        $this->assertSame('FLT-001', $product->code);
        $this->assertSame('15.50', $product->sale_price);
        $this->assertTrue($product->is_active);
        $this->assertSame(2, $product->inventories()->count());
        $this->assertSame(0, (int) $product->inventories()->sum('quantity'));
        $this->assertSame($boss->id, $product->created_by);
    }

    public function test_a_secondary_boss_can_create_an_article(): void
    {
        $boss = User::factory()->bossSecondaire()->create();

        $this->actingAs($boss)->post('/articles', [
            'code' => 'SEC-010',
            'name' => 'Ampoule phare',
            'category' => 'Éclairage',
            'sale_price' => '8.00',
        ])->assertRedirect();

        $this->assertDatabaseHas('products', [
            'organization_id' => $boss->organization_id,
            'code' => 'SEC-010',
            'sale_price' => '8.00',
        ]);
    }

    public function test_the_same_code_can_exist_in_another_organization(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $other = User::factory()->bossPrincipal()->create();

        Product::factory()->create([
            'organization_id' => $other->organization_id,
            'code' => 'FLT-001',
        ]);

        $this->actingAs($boss)->post('/articles', [
            'code' => 'FLT-001',
            'name' => 'Filtre local',
            'category' => 'Pièces moteur',
            'sale_price' => '12.00',
        ])->assertRedirect();

        $this->assertSame(2, Product::query()->where('code', 'FLT-001')->count());
    }

    public function test_several_articles_can_omit_a_barcode(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)->post('/articles', [
            'code' => 'NO-BAR-1',
            'name' => 'Article sans code-barres',
            'category' => 'Accessoires',
            'sale_price' => '5.00',
        ])->assertRedirect();

        $this->actingAs($boss)->post('/articles', [
            'code' => 'NO-BAR-2',
            'name' => 'Autre article sans code-barres',
            'category' => 'Accessoires',
            'sale_price' => '6.00',
        ])->assertRedirect();

        $this->assertSame(2, Product::query()->whereNull('barcode')->count());
    }

    public function test_barcodes_are_unique_in_the_organization(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'barcode' => '1234567890123',
        ]);

        $this->actingAs($boss)->post('/articles', [
            'code' => 'BAR-002',
            'barcode' => '1234567890123',
            'name' => 'Doublon code-barres',
            'category' => 'Accessoires',
            'sale_price' => '7.00',
        ])->assertSessionHasErrors('barcode');
    }

    public function test_article_codes_are_unique_in_the_organization(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'code' => 'FLT-001',
        ]);

        $this->actingAs($boss)->post('/articles', [
            'code' => 'FLT-001',
            'name' => 'Autre filtre',
            'category' => 'Pièces moteur',
            'sale_price' => '10.00',
        ])->assertSessionHasErrors('code');
    }

    public function test_a_boss_can_update_the_sale_price(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'sale_price' => '10.00',
        ]);

        $this->actingAs($boss)->put('/articles/'.$product->id, [
            'code' => $product->code,
            'name' => $product->name,
            'category' => $product->category,
            'description' => $product->description,
            'sale_price' => '25.00',
        ])->assertRedirect();

        $this->assertSame('25.00', $product->fresh()->sale_price);
    }

    public function test_an_employee_cannot_create_or_update_an_article_or_its_price(): void
    {
        $employee = User::factory()->employe()->create();
        $product = Product::factory()->create([
            'organization_id' => $employee->organization_id,
            'sale_price' => '10.00',
        ]);

        $this->actingAs($employee)->get('/articles/nouveau')->assertForbidden();
        $this->actingAs($employee)->get('/articles/'.$product->id.'/modifier')->assertForbidden();

        $this->actingAs($employee)->post('/articles', [
            'code' => 'EMP-001',
            'name' => 'Intrusion',
            'category' => 'Pièces moteur',
            'sale_price' => '9.00',
        ])->assertForbidden();

        $this->actingAs($employee)->put('/articles/'.$product->id, [
            'code' => $product->code,
            'name' => $product->name,
            'category' => $product->category,
            'sale_price' => '1.00',
        ])->assertForbidden();

        $this->assertSame('10.00', $product->fresh()->sale_price);
    }

    public function test_an_employee_can_search_and_view_articles_including_prices(): void
    {
        $employee = User::factory()->employe()->create();
        $product = Product::factory()->create([
            'organization_id' => $employee->organization_id,
            'code' => 'SRC-100',
            'name' => 'Plaquette de frein',
            'sale_price' => '42.00',
        ]);

        $this->actingAs($employee)
            ->get('/articles?q=SRC-100')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Products/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Plaquette de frein')
                ->where('products.data.0.sale_price', '42.00')
            );

        $this->actingAs($employee)
            ->get('/articles/'.$product->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Products/Show')
                ->where('product.sale_price', '42.00')
                ->where('canManage', false)
            );
    }

    public function test_deactivating_an_article_keeps_its_history(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
        ]);

        $this->actingAs($boss)
            ->post('/articles/'.$product->id.'/desactiver')
            ->assertRedirect();

        $this->assertFalse($product->fresh()->is_active);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_a_product_from_another_organization_is_not_accessible(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $foreign = Product::factory()->create();

        $this->actingAs($boss)->get('/articles/'.$foreign->id)->assertNotFound();
    }
}
