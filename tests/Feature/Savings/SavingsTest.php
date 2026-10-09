<?php

namespace Tests\Feature\Savings;

use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\LocationProvisioner;
use App\Services\SaleService;
use App\Services\SavingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SavingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_savings_are_a_suggestion_computed_from_real_revenue(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)
            ->get(route('savings.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('suggestion.available', false)
                ->where('suggestion.automatic', false)
                ->where('suggestion.suggested_amount', null)
                ->where('suggestion.revenue', null)
            );

        $empty = $this->actingAs($boss)->get(route('savings.show'))->getContent();
        $this->assertStringNotContainsString('Retirer', $empty);
        $this->assertStringNotContainsString('Appliquer', $empty);

        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'sale_price' => '300.00',
            'purchase_price' => '100.00',
        ]);
        app(InventoryService::class)->receive(
            $boss,
            $product,
            app(LocationProvisioner::class)->boutique($boss->organization),
            1,
            'Stock',
        );
        app(SaleService::class)->sell($boss, $product, 1);

        $suggestion = app(SavingsService::class)->suggestion($boss->organization_id);

        $this->assertTrue($suggestion['available']);
        $this->assertFalse($suggestion['automatic']);
        $this->assertSame('300.00', $suggestion['revenue']);
        $this->assertSame('10.00', $suggestion['daily_average']);
        $this->assertSame('1.00', $suggestion['suggested_amount']);
        $this->assertSame('Épargne suggérée : 1.00', $suggestion['message']);

        $this->actingAs($boss)
            ->get(route('savings.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('suggestion.available', true)
                ->where('suggestion.automatic', false)
                ->where('suggestion.revenue', '300.00')
                ->where('suggestion.daily_average', '10.00')
                ->where('suggestion.suggested_amount', '1.00')
                ->where('suggestion.message', 'Épargne suggérée : 1.00')
            );
    }

    public function test_there_is_no_route_that_withdraws_savings_from_cash(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)->post('/epargne')->assertStatus(405);
    }
}
