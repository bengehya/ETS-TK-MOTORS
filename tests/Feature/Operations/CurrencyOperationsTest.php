<?php

namespace Tests\Feature\Operations;

use App\Enums\CashDeclarationStatus;
use App\Enums\Currency;
use App\Enums\RestockSuggestionStatus;
use App\Enums\SaleStatus;
use App\Models\CashAccount;
use App\Models\CashDeclaration;
use App\Models\CashEntry;
use App\Models\Exchange;
use App\Models\Product;
use App\Models\RestockSuggestion;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CustomerRequestService;
use App\Services\ExchangeService;
use App\Services\InventoryService;
use App\Services\LocationProvisioner;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CurrencyOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_keeps_protected_routes_and_hides_rentals(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create(['organization_id' => $boss->organization_id]);

        $this->actingAs($boss)->get(route('stocks.overview'))->assertOk();
        $this->actingAs($boss)->get(route('products.index'))->assertOk();
        $this->actingAs($boss)->get(route('sales.index'))->assertOk();
        $this->actingAs($boss)->get(route('cash.index'))->assertOk();
        $this->actingAs($boss)->get(route('expenses.index'))->assertOk();
        $this->actingAs($boss)->get(route('savings.show'))->assertOk();
        $this->actingAs($boss)->get(route('cash.exchange'))->assertOk();
        $this->actingAs($boss)->get(route('cash.counts.index'))->assertOk();
        $this->actingAs($boss)->get(route('requests.index'))->assertOk();
        $this->actingAs($boss)->get(route('rentals.index'))->assertNotFound();

        $this->actingAs($employee)->get(route('cash.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('savings.show'))->assertForbidden();
        $this->actingAs($employee)->get(route('cash.exchange'))->assertForbidden();
        $this->actingAs($employee)->get(route('cash.counts.index'))->assertOk();
        $this->actingAs($employee)->get(route('rentals.index'))->assertForbidden();
        $this->actingAs($employee)->post(route('cash.rates.store'), ['cdf_per_usd' => '2300'])->assertForbidden();
        $this->actingAs($employee)->post(route('cash.exchange.store'), [
            'source_currency' => 'USD',
            'amount' => '1.00',
        ])->assertForbidden();
    }

    public function test_a_multi_line_sale_books_the_net_amount_and_can_be_reprinted(): void
    {
        [$boss, $employee, $first, $second] = $this->prepareTwoProducts();
        $token = '11111111-1111-1111-1111-111111111111';

        $this->actingAs($employee)
            ->post(route('sales.store'), [
                'lines' => [
                    ['product_id' => $first->id, 'quantity' => 1, 'unit_sale_price' => '1.00'],
                    ['product_id' => $second->id, 'quantity' => 2, 'unit_sale_price' => '1.00'],
                ],
                'currency' => 'USD',
                'amount_received' => '30.00',
                'client_token' => $token,
            ])
            ->assertRedirect();

        $sale = Sale::query()->firstOrFail();
        $this->assertSame('25.00', $sale->line_total);
        $this->assertSame('30.00', $sale->amount_received);
        $this->assertSame('5.00', $sale->change_given);
        $this->assertSame(Currency::Usd, $sale->currency);
        $this->assertCount(2, $sale->lines);
        $this->assertSame('25.00', CashAccount::query()->where('currency', 'USD')->value('balance'));
        $this->assertSame(2, StockMovement::query()->where('reference_id', $sale->id)->count());

        $this->actingAs($employee)
            ->post(route('sales.store'), [
                'lines' => [
                    ['product_id' => $first->id, 'quantity' => 1],
                ],
                'currency' => 'USD',
                'amount_received' => '10.00',
                'client_token' => $token,
            ])
            ->assertSessionHasErrors('client_token');

        $this->assertSame(1, Sale::query()->count());

        $this->actingAs($employee)
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sale.line_total', '25.00')
                ->where('sale.change_given', '5.00')
                ->has('sale.lines', 2)
                ->missing('sale.profit')
            );

        $salesBeforePrint = Sale::query()->count();
        $entriesBeforePrint = CashEntry::query()->count();

        $this->actingAs($employee)
            ->get(route('sales.invoice', $sale))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Invoice')
                ->where('sale.reference', $sale->reference)
                ->where('company.name', 'ETS TK MOTORS')
                ->where('company.slogan', 'Votre Moto, Notre Passion !')
                ->has('sale.lines', 2)
                ->missing('sale.profit')
            );

        $this->assertSame($salesBeforePrint, Sale::query()->count());
        $this->assertSame($entriesBeforePrint, CashEntry::query()->count());

        $this->actingAs($employee)
            ->post(route('sales.store'), [
                'lines' => [
                    ['product_id' => $first->id, 'quantity' => 1],
                    ['product_id' => $second->id, 'quantity' => 5],
                ],
                'currency' => 'USD',
                'amount_received' => '100.00',
                'client_token' => '22222222-2222-2222-2222-222222222222',
            ])
            ->assertSessionHasErrors('lines.1.quantity');

        $this->assertSame(1, Sale::query()->count());
        $this->assertSame('25.00', CashAccount::query()->where('currency', 'USD')->value('balance'));
    }

    public function test_a_cdf_sale_does_not_change_the_usd_balance_and_short_payment_is_refused(): void
    {
        [$boss, $employee, $product] = $this->prepareOneProduct();

        $this->actingAs($employee)
            ->post(route('sales.store'), [
                'lines' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'currency' => 'CDF',
                'amount_received' => '5.00',
            ])
            ->assertSessionHasErrors('amount_received');

        $this->assertSame(0, Sale::query()->count());

        $sale = app(SaleService::class)->sellCart($employee, [
            ['product' => $product, 'quantity' => 1],
        ], Currency::Cdf, '20.00');

        $this->assertSame('19.00', $sale->line_total);
        $this->assertSame('1.00', $sale->change_given);
        $this->assertSame('19.00', CashAccount::query()->where('currency', 'CDF')->value('balance'));
        $this->assertNull(CashAccount::query()->where('currency', 'USD')->first());

        $this->actingAs($boss)
            ->post(route('sales.cancel', $sale), ['reason' => 'Retour'])
            ->assertRedirect();

        $this->assertSame(SaleStatus::Cancelled, $sale->refresh()->status);
        $this->assertSame('0.00', CashAccount::query()->where('currency', 'CDF')->value('balance'));
    }

    public function test_exchange_keeps_its_historical_rate_and_refuses_insufficient_funds(): void
    {
        [$boss, $employee, $product] = $this->prepareOneProduct();
        app(SaleService::class)->sell($boss, $product, 3);

        $this->actingAs($boss)
            ->post(route('cash.exchange.store'), [
                'source_currency' => 'USD',
                'amount' => '10.00',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Exchange::query()->count());

        app(ExchangeService::class)->setRate($boss, '2300');
        $exchange = app(ExchangeService::class)->convert($boss, Currency::Usd, '10.00');

        $this->assertSame('2300.0000', $exchange->rate);
        $this->assertSame('23000.00', $exchange->destination_amount);
        $this->assertSame('47.00', CashAccount::query()->where('currency', 'USD')->value('balance'));
        $this->assertSame('23000.00', CashAccount::query()->where('currency', 'CDF')->value('balance'));
        $this->assertSame(1, CashEntry::query()->where('source_type', Exchange::class)->where('source_id', $exchange->id)->where('direction', 'outflow')->count());
        $this->assertSame(1, CashEntry::query()->where('source_type', Exchange::class)->where('source_id', $exchange->id)->where('direction', 'inflow')->count());

        app(ExchangeService::class)->setRate($boss, '2400');
        $this->assertSame('2300.0000', $exchange->refresh()->rate);
        $this->assertSame('47.00', CashAccount::query()->where('currency', 'USD')->value('balance'));

        $back = app(ExchangeService::class)->convert($boss, Currency::Cdf, '2400.00');
        $this->assertSame('2400.0000', $back->rate);
        $this->assertSame('1.00', $back->destination_amount);
        $this->assertSame('48.00', CashAccount::query()->where('currency', 'USD')->value('balance'));
        $this->assertSame('20600.00', CashAccount::query()->where('currency', 'CDF')->value('balance'));

        $this->actingAs($boss)
            ->post(route('cash.exchange.store'), [
                'source_currency' => 'USD',
                'amount' => '1000.00',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame('48.00', CashAccount::query()->where('currency', 'USD')->value('balance'));
        $this->assertSame(2, Exchange::query()->count());
    }

    public function test_cash_counts_do_not_change_balances_and_signal_a_gap(): void
    {
        [$boss, $employee, $product] = $this->prepareOneProduct();
        app(SaleService::class)->sell($boss, $product, 2);
        app(ExchangeService::class)->setRate($boss, '2300');

        $this->actingAs($employee)
            ->post(route('cash.counts.store'), [
                'note' => 'La caisse contient 40 USD en billets et 138000 FC.',
                'counted_usd' => '40.00',
                'counted_cdf' => '138000.00',
            ])
            ->assertRedirect();

        $declaration = CashDeclaration::query()->firstOrFail();
        $this->assertSame('38.00', CashAccount::query()->where('currency', 'USD')->value('balance'));
        $this->assertSame('40.00', $declaration->counted_usd);
        $this->assertSame('2.00', $declaration->gap_usd);
        $this->assertSame('100.00', $declaration->indicative_usd);

        $this->actingAs($employee)
            ->post(route('cash.counts.validate', $declaration))
            ->assertForbidden();

        $this->actingAs($boss)
            ->post(route('cash.counts.validate', $declaration))
            ->assertRedirect();

        $this->assertSame(CashDeclarationStatus::Validated, $declaration->refresh()->status);
        $this->assertSame('38.00', CashAccount::query()->where('currency', 'USD')->value('balance'));
    }

    public function test_repeated_requests_create_a_suggestion_without_stock_movement(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create(['organization_id' => $boss->organization_id]);
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'name' => 'Courroie',
        ]);
        $service = app(CustomerRequestService::class);
        $movements = StockMovement::query()->count();

        $service->record($employee, $product, null, 2, false, null);
        $service->record($employee, $product, null, 1, false, null);
        $this->assertSame(0, RestockSuggestion::query()->count());

        $service->record($boss, $product, null, 4, false, 'Rupture');

        $suggestion = RestockSuggestion::query()->firstOrFail();
        $this->assertSame(3, $suggestion->request_count);
        $this->assertSame(7, $suggestion->total_quantity);
        $this->assertSame(30, $suggestion->observation_days);
        $this->assertSame(RestockSuggestionStatus::Open, $suggestion->status);
        $this->assertSame($movements, StockMovement::query()->count());
        $this->assertSame(0, CashEntry::query()->count());

        $this->actingAs($employee)
            ->get(route('requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('suggestions', 0)->has('requests.data', 2));

        $this->actingAs($boss)
            ->post(route('requests.suggestions.update', $suggestion), [
                'status' => 'decided',
                'justification' => 'Commander au prochain arrivage',
            ])
            ->assertRedirect();

        $this->assertSame(RestockSuggestionStatus::Decided, $suggestion->refresh()->status);
        $this->assertSame($movements, StockMovement::query()->count());

        $this->actingAs($employee)
            ->get(route('requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('suggestions', 1));

        $free = $service->record($employee, null, null, 1, false, null, 'Rétroviseur absent');
        $this->assertSame('Rétroviseur absent', $free->designation);
        $this->assertNull($free->product_id);
    }

    public function test_dashboard_profit_excludes_cancelled_sales_and_keeps_currencies_apart(): void
    {
        [$boss, $employee, $product] = $this->prepareOneProduct();
        $sale = app(SaleService::class)->sell($boss, $product, 2);
        app(SaleService::class)->cancel($boss, $sale, 'Erreur');
        app(SaleService::class)->sellCart($boss, [
            ['product' => $product, 'quantity' => 1],
        ], Currency::Cdf, '19.00');

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('finance.sales.today_amount', '0.00')
                ->where('finance.profit.amount', '0.00')
                ->where('finance.cash.usd', '0.00')
                ->where('finance.cash.cdf', '19.00')
                ->where('finance.profit.by_currency.CDF.gross_profit', '11.87')
                ->has('finance.top_sold', 0)
            );
    }

    /**
     * @return array{0: User, 1: User, 2: Product, 3: Product}
     */
    private function prepareTwoProducts(): array
    {
        [$boss, $employee, $first] = $this->prepareOneProduct();
        $second = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'name' => 'Ampoule',
            'sale_price' => '3.00',
            'purchase_price' => '1.00',
        ]);
        $boutique = app(LocationProvisioner::class)->boutique($boss->organization);
        app(InventoryService::class)->receive($boss, $second, $boutique, 4, 'Stock test');

        return [$boss, $employee, $first, $second];
    }

    /**
     * @return array{0: User, 1: User, 2: Product}
     */
    private function prepareOneProduct(): array
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create(['organization_id' => $boss->organization_id]);
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'name' => 'Plaquette frein',
            'sale_price' => '19.00',
            'purchase_price' => '7.13',
        ]);
        $boutique = app(LocationProvisioner::class)->boutique($boss->organization);
        app(InventoryService::class)->receive($boss, $product, $boutique, 5, 'Stock test');

        return [$boss, $employee, $product];
    }
}
