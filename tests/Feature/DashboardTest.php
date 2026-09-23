<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\LocationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_dashboard(): void
    {
        $user = User::factory()->bossPrincipal()->create([
            'name' => 'Patron Principal',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Patron Principal', false);
        $response->assertSee('BOSS_PRINCIPAL', false);
        $response->assertSee($user->organization->name, false);
        $response->assertSee('Le module caisse n’est pas encore disponible', false);
        $response->assertDontSee('chiffre d’affaires', false);
    }

    public function test_a_boss_receives_finance_placeholders_without_invented_amounts(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('canViewFinance', true)
                ->where('finance.sales.available', false)
                ->where('finance.sales.today_amount', null)
                ->where('finance.profit.available', false)
                ->where('finance.profit.amount', null)
                ->where('finance.cash.available', false)
                ->where('finance.cash.amount', null)
                ->where('finance.expenses.available', false)
                ->where('finance.expenses.total', null)
                ->where('finance.chart.empty', true)
                ->has('finance.top_sold', 0)
                ->has('stock')
                ->has('arrivals')
            );
    }

    public function test_an_employee_never_receives_finance_payloads(): void
    {
        $employee = User::factory()->employe()->create();

        $this->actingAs($employee)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('canViewFinance', false)
                ->missing('finance')
                ->has('stock')
                ->has('arrivals')
            );

        $html = $this->actingAs($employee)->get('/dashboard')->getContent();
        $this->assertStringNotContainsString('Montant en caisse', $html);
        $this->assertStringNotContainsString('Bénéfice du jour', $html);
        $this->assertStringNotContainsString('module dépenses', $html);
        $this->assertStringNotContainsString('module caisse', $html);
    }

    public function test_a_secondary_boss_can_see_finance_placeholders_but_not_invented_cash(): void
    {
        $boss = User::factory()->bossSecondaire()->create();

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewFinance', true)
                ->where('finance.cash.available', false)
                ->where('finance.cash.amount', null)
            );
    }

    public function test_dashboard_uses_real_sale_movements_for_rankings_without_inventing_amounts(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'name' => 'Plaquette frein',
        ]);
        $locations = app(LocationProvisioner::class);
        $service = app(InventoryService::class);

        $service->receive($boss, $product, $locations->boutique($boss->organization), 10, 'Préparation test');
        $service->consumeForSale($boss, $product, 3);

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('finance.sales.today_quantity', 3)
                ->where('finance.sales.today_amount', null)
                ->where('finance.top_sold.0.name', 'Plaquette frein')
                ->where('finance.top_sold.0.quantity_sold', 3)
                ->where('finance.top_sold.0.amount', null)
                ->where('finance.chart.empty', false)
            );
    }

    public function test_dashboard_period_defaults_when_invalid(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)
            ->get('/dashboard?periode=inconnu')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('periode', 'jour'));

        $this->actingAs($boss)
            ->get('/dashboard?periode=semaine')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('periode', 'semaine'));
    }

    public function test_authenticated_users_see_the_splash_on_the_home_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
            );
    }
}
