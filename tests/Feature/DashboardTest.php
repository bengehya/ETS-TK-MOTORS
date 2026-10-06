<?php

namespace Tests\Feature;

use App\Enums\Civility;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\LocationProvisioner;
use App\Services\SaleService;
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
        $response->assertSee('Bienvenue dans TK MOTORS, Patron Principal', false);
        $response->assertSee('Votre Moto, Notre Passion !', false);
        $response->assertDontSee('Utilisateur connecté', false);
        $response->assertInertia(fn (Assert $page) => $page
            ->where('finance.cash.available', true)
            ->where('finance.cash.amount', '0.00')
        );
        $response->assertDontSee('chiffre d’affaires', false);
    }

    public function test_a_boss_sees_real_zero_finance_when_nothing_was_recorded(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('welcome', 'Bienvenue dans TK MOTORS, '.$boss->name)
                ->where('canViewFinance', true)
                ->where('finance.sales.available', true)
                ->where('finance.sales.today_amount', '0.00')
                ->where('finance.profit.available', true)
                ->where('finance.profit.amount', '0.00')
                ->where('finance.cash.available', true)
                ->where('finance.cash.amount', '0.00')
                ->where('finance.expenses.available', true)
                ->where('finance.expenses.total', '0.00')
                ->where('finance.chart.empty', true)
                ->where('stock.low_stock.threshold_defined', true)
                ->where('stock.low_stock.count', 0)
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
                ->where('welcome', 'Bienvenue dans TK MOTORS, '.$employee->name)
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

    public function test_a_secondary_boss_sees_a_real_cash_balance(): void
    {
        $boss = User::factory()->bossSecondaire()->create();

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('welcome', 'Bienvenue dans TK MOTORS, '.$boss->name)
                ->where('canViewFinance', true)
                ->where('finance.cash.available', true)
                ->where('finance.cash.amount', '0.00')
            );
    }

    public function test_dashboard_uses_completed_sales_for_rankings_and_amounts(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'name' => 'Plaquette frein',
            'sale_price' => '25.00',
            'purchase_price' => '10.00',
        ]);
        $locations = app(LocationProvisioner::class);

        app(InventoryService::class)->receive($boss, $product, $locations->boutique($boss->organization), 10, 'Préparation test');
        app(SaleService::class)->sell($boss, $product, 3);

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('finance.sales.today_quantity', 3)
                ->where('finance.sales.today_amount', '75.00')
                ->where('finance.profit.amount', '45.00')
                ->where('finance.cash.amount', '75.00')
                ->where('finance.top_sold.0.name', 'Plaquette frein')
                ->where('finance.top_sold.0.quantity_sold', 3)
                ->where('finance.top_sold.0.amount', '75.00')
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

    public function test_welcome_uses_civility_only_when_it_was_provided(): void
    {
        $boss = User::factory()->bossPrincipal()->create([
            'name' => 'Tresor Kalumbi',
            'first_name' => 'Tresor',
            'last_name' => 'Kalumbi',
            'email' => 'madame.kalumbi@tkmotors.test',
            'civility' => null,
        ]);

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('welcome', 'Bienvenue dans TK MOTORS, Tresor Kalumbi')
            );

        $boss->forceFill(['civility' => Civility::Monsieur])->save();

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('welcome', 'Bienvenue dans TK MOTORS, Monsieur Tresor Kalumbi')
            );

        $boss->forceFill(['civility' => Civility::Madame])->save();

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('welcome', 'Bienvenue dans TK MOTORS, Madame Tresor Kalumbi')
            );
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
