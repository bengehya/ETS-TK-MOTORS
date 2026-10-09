<?php

namespace Tests\Feature\Operations;

use App\Enums\Currency;
use App\Exceptions\InsufficientCashException;
use App\Models\AuditLog;
use App\Models\CashAccount;
use App\Models\CashEntry;
use App\Models\Exchange;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\CashService;
use App\Services\ExchangeService;
use App\Services\InventoryService;
use App\Services\LocationProvisioner;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class PaymentExchangeCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_pages_load_for_both_roles_and_payment_rules_hold(): void
    {
        [$boss, $employee, $first, $second] = $this->prepareTwoProducts();

        $this->actingAs($employee)->get(route('sales.create'))->assertOk();
        $this->actingAs($boss)->get(route('sales.create'))->assertOk();

        $exact = app(SaleService::class)->sellCart($employee, [
            ['product' => $first, 'quantity' => 1],
            ['product' => $second, 'quantity' => 2],
        ], Currency::Usd, '25.00');

        $this->assertSame('25.00', $exact->line_total);
        $this->assertSame('0.00', $exact->change_given);
        $this->assertSame('25.00', CashAccount::query()->where('currency', 'USD')->value('balance'));

        $this->actingAs($employee)
            ->post(route('sales.store'), [
                'lines' => [
                    ['product_id' => $first->id, 'quantity' => 1],
                ],
                'currency' => 'USD',
                'amount_received' => '30,00',
            ])
            ->assertRedirect();

        $commaSale = Sale::query()->whereKeyNot($exact->id)->firstOrFail();
        $this->assertSame('19.00', $commaSale->line_total);
        $this->assertSame('30.00', $commaSale->amount_received);
        $this->assertSame('11.00', $commaSale->change_given);
        $this->assertSame('44.00', CashAccount::query()->where('currency', 'USD')->value('balance'));

        $cdf = app(SaleService::class)->sellCart($employee, [
            ['product' => $first, 'quantity' => 1],
            ['product' => $second, 'quantity' => 1],
        ], Currency::Cdf, '22.00');

        $this->assertSame('22.00', $cdf->line_total);
        $this->assertSame('0.00', $cdf->change_given);
        $this->assertSame('22.00', CashAccount::query()->where('currency', 'CDF')->value('balance'));
        $this->assertSame('44.00', CashAccount::query()->where('currency', 'USD')->value('balance'));

        $this->actingAs($employee)
            ->post(route('sales.store'), [
                'lines' => [
                    ['product_id' => $first->id, 'quantity' => 1],
                ],
                'currency' => 'CDF',
                'amount_received' => '5.00',
            ])
            ->assertSessionHasErrors('amount_received');

        $this->assertSame(3, Sale::query()->count());
        $this->assertSame('22.00', CashAccount::query()->where('currency', 'CDF')->value('balance'));
        $this->assertSame(1, AuditLog::query()->where('action', 'sale.payment_insufficient')->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'system.error')->count());

        $payment = AuditLog::query()->where('action', 'sale.payment_insufficient')->firstOrFail();
        $this->assertSame('failure', $payment->result);
        $this->assertSame('EMPLOYE', $payment->actor_role);
        $this->assertSame($employee->id, $payment->user_id);
    }

    public function test_exchange_updates_both_balances_and_preserves_history(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $this->seedCash($boss, '50.00', '0.00');
        app(ExchangeService::class)->setRate($boss, '2300');

        $this->actingAs($boss)
            ->get(route('cash.exchange', ['amount' => '30.00', 'source_currency' => 'USD']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('preview.available', true)
                ->where('preview.destination_amount', '69000.00')
                ->where('preview.balances_before.USD', '50.00')
                ->where('preview.balances_before.CDF', '0.00')
                ->where('preview.balances_after.USD', '20.00')
                ->where('preview.balances_after.CDF', '69000.00')
                ->where('balances.USD', '50.00')
                ->where('balances.CDF', '0.00')
            );

        $this->actingAs($boss)
            ->post(route('cash.exchange.store'), [
                'source_currency' => 'USD',
                'amount' => '30.00',
            ])
            ->assertRedirect()
            ->assertSessionHas('exchange_result', function (array $result): bool {
                return $result['before']['USD'] === '50.00'
                    && $result['before']['CDF'] === '0.00'
                    && $result['after']['USD'] === '20.00'
                    && $result['after']['CDF'] === '69000.00'
                    && $result['fee_amount'] === '0.00'
                    && $result['rate'] === '2300.0000';
            });

        $this->assertSame('20.00', $this->balance('USD'));
        $this->assertSame('69000.00', $this->balance('CDF'));

        $exchange = Exchange::query()->firstOrFail();
        $this->assertSame('69000.00', $exchange->destination_amount);
        $this->assertSame('0.00', $exchange->fee_amount);
        $this->assertSame($boss->id, $exchange->created_by);

        app(ExchangeService::class)->setRate($boss, '2500');
        $this->assertSame('2300.0000', $exchange->refresh()->rate);
        $this->assertSame('20.00', $this->balance('USD'));
        $this->assertSame('69000.00', $this->balance('CDF'));

        $reverse = app(ExchangeService::class)->convert($boss, Currency::Cdf, '2500.00');
        $this->assertSame('2500.0000', $reverse->rate);
        $this->assertSame('1.00', $reverse->destination_amount);
        $this->assertSame('21.00', $this->balance('USD'));
        $this->assertSame('66500.00', $this->balance('CDF'));
        $this->assertSame('2300.0000', $exchange->refresh()->rate);

        $this->actingAs($boss)
            ->get(route('cash.exchange'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('balances.USD', '21.00')
                ->where('balances.CDF', '66500.00')
                ->has('exchanges', 2)
            );
    }

    public function test_exchange_refuses_an_insufficient_or_zero_source_without_changing_balances(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $this->seedCash($boss, '0.00', '0.00');
        app(ExchangeService::class)->setRate($boss, '2300');

        $this->actingAs($boss)
            ->post(route('cash.exchange.store'), [
                'source_currency' => 'USD',
                'amount' => '10.00',
            ])
            ->assertSessionHasErrors(['amount' => 'Solde USD insuffisant.']);

        $this->assertSame(0, Exchange::query()->count());
        $this->assertSame('0.00', $this->balance('USD'));
        $this->assertSame('0.00', $this->balance('CDF'));
        $this->assertSame(0, CashEntry::query()->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'exchange.refused')->count());

        CashAccount::query()->where('currency', 'USD')->update(['balance' => '20.00']);

        $this->actingAs($boss)
            ->post(route('cash.exchange.store'), [
                'source_currency' => 'CDF',
                'amount' => '100.00',
            ])
            ->assertSessionHasErrors(['amount' => 'Solde CDF insuffisant.']);

        $this->assertSame('20.00', $this->balance('USD'));
        $this->assertSame('0.00', $this->balance('CDF'));
        $this->assertSame(0, Exchange::query()->count());
    }

    public function test_a_second_exchange_cannot_spend_the_same_balance_and_locks_the_accounts(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $this->seedCash($boss, '50.00', '0.00');
        app(ExchangeService::class)->setRate($boss, '2300');

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        app(ExchangeService::class)->convert($boss, Currency::Usd, '30.00');

        try {
            app(ExchangeService::class)->convert($boss, Currency::Usd, '30.00');
            $this->fail('Le second change aurait dû être refusé.');
        } catch (InsufficientCashException $exception) {
            $this->assertSame('Solde USD insuffisant.', $exception->getMessage());
        }

        $this->assertSame('20.00', $this->balance('USD'));
        $this->assertSame('69000.00', $this->balance('CDF'));
        $this->assertSame(1, Exchange::query()->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'exchange.refused')->count());
        $this->assertTrue(collect($queries)->contains(
            fn (string $sql): bool => str_contains($sql, 'cash_accounts'),
        ));
        $this->assertStringContainsString(
            'lockForUpdate()',
            (string) file_get_contents(app_path('Services/CashService.php')),
        );
    }

    public function test_a_failure_during_exchange_rolls_the_balances_back(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $this->seedCash($boss, '50.00', '0.00');
        app(ExchangeService::class)->setRate($boss, '2300');

        $mock = \Mockery::mock(app(CashService::class))->makePartial();
        $mock->shouldReceive('movePair')->once()->andThrow(new RuntimeException('panne forcée'));
        $this->app->instance(CashService::class, $mock);

        try {
            app(ExchangeService::class)->convert($boss, Currency::Usd, '30.00');
            $this->fail('La panne aurait dû interrompre le change.');
        } catch (RuntimeException $exception) {
            $this->assertSame('panne forcée', $exception->getMessage());
        }

        $this->assertSame(0, Exchange::query()->count());
        $this->assertSame(0, CashEntry::query()->count());
        $this->assertSame('50.00', $this->balance('USD'));
        $this->assertSame('0.00', $this->balance('CDF'));
    }

    /**
     * @return array{0: User, 1: User, 2: Product, 3: Product}
     */
    private function prepareTwoProducts(): array
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create(['organization_id' => $boss->organization_id]);
        $first = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'sale_price' => '19.00',
            'purchase_price' => '7.13',
        ]);
        $second = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'sale_price' => '3.00',
            'purchase_price' => '1.00',
        ]);
        $boutique = app(LocationProvisioner::class)->boutique($boss->organization);
        app(InventoryService::class)->receive($boss, $first, $boutique, 5, 'Stock test');
        app(InventoryService::class)->receive($boss, $second, $boutique, 5, 'Stock test');

        return [$boss, $employee, $first, $second];
    }

    private function seedCash(User $boss, string $usd, string $cdf): void
    {
        CashAccount::query()->create([
            'organization_id' => $boss->organization_id,
            'currency' => Currency::Usd,
            'balance' => $usd,
        ]);
        CashAccount::query()->create([
            'organization_id' => $boss->organization_id,
            'currency' => Currency::Cdf,
            'balance' => $cdf,
        ]);
    }

    private function balance(string $currency): ?string
    {
        $balance = CashAccount::query()->where('currency', $currency)->value('balance');

        return $balance === null ? null : (string) $balance;
    }
}
