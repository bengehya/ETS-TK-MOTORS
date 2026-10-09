<?php

namespace Tests\Feature\Finance;

use App\Enums\ExpenseStatus;
use App\Models\CashAccount;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\InventoryService;
use App\Services\LocationProvisioner;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashAndExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_validated_expense_decreases_cash_and_an_insufficient_one_is_refused(): void
    {
        [$boss, $product] = $this->fundedBoss();
        app(SaleService::class)->sell($boss, $product, 2);

        $this->assertSame('40.00', CashAccount::query()->value('balance'));

        $pending = app(ExpenseService::class)->create($boss, '15.00', 'Carburant', now()->toDateString());
        $this->assertSame(ExpenseStatus::Pending, $pending->status);
        $this->assertSame('40.00', CashAccount::query()->value('balance'));

        $validated = app(ExpenseService::class)->validate($boss, $pending);
        $this->assertSame(ExpenseStatus::Validated, $validated->status);
        $this->assertNotNull($validated->cash_entry_id);
        $this->assertSame('25.00', CashAccount::query()->value('balance'));

        $tooMuch = app(ExpenseService::class)->create($boss, '80.00', 'Loyer', now()->toDateString());
        $refused = app(ExpenseService::class)->validate($boss, $tooMuch);

        $this->assertSame(ExpenseStatus::Refused, $refused->status);
        $this->assertNull($refused->cash_entry_id);
        $this->assertSame('25.00', CashAccount::query()->value('balance'));
        $this->assertSame(2, Expense::query()->count());

        $this->actingAs($boss)
            ->get(route('cash.index'))
            ->assertOk()
            ->assertSee('25.00', false);

        $this->actingAs($boss)
            ->get(route('expenses.show', $refused))
            ->assertOk()
            ->assertSee('Solde de caisse insuffisant', false);
    }

    public function test_a_boss_can_refuse_an_expense_without_touching_cash(): void
    {
        [$boss] = $this->fundedBoss();

        $this->actingAs($boss)
            ->post(route('expenses.store'), [
                'amount' => '12.50',
                'reason' => 'Fournitures',
                'spent_on' => now()->toDateString(),
            ])
            ->assertRedirect();

        $expense = Expense::query()->firstOrFail();

        $this->actingAs($boss)
            ->post(route('expenses.refuse', $expense), ['decision_note' => 'Hors budget'])
            ->assertRedirect(route('expenses.show', $expense));

        $this->assertSame(ExpenseStatus::Refused, $expense->refresh()->status);
        $this->assertNull(CashAccount::query()->first());
    }

    public function test_cancelling_a_sale_fails_when_cash_cannot_cover_the_refund(): void
    {
        [$boss, $product] = $this->fundedBoss();
        $sale = app(SaleService::class)->sell($boss, $product, 1);
        $expense = app(ExpenseService::class)->create($boss, '20.00', 'Tout le produit', now()->toDateString());
        app(ExpenseService::class)->validate($boss, $expense);

        $this->actingAs($boss)
            ->post(route('sales.cancel', $sale), ['reason' => 'Retour client'])
            ->assertSessionHasErrors('reason');

        $this->assertSame('completed', $sale->refresh()->status->value);
        $this->assertSame('0.00', CashAccount::query()->value('balance'));
        $boutique = app(LocationProvisioner::class)->boutique($boss->organization);
        $this->assertSame(4, (int) $product->inventories()->where('location_id', $boutique->id)->value('quantity'));
    }

    public function test_an_employee_cannot_create_an_expense(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create(['organization_id' => $boss->organization_id]);

        $this->actingAs($employee)
            ->post(route('expenses.store'), [
                'amount' => '5.00',
                'reason' => 'Test',
                'spent_on' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->assertSame(0, Expense::query()->count());
    }

    /**
     * @return array{0: User, 1: Product}
     */
    private function fundedBoss(): array
    {
        $boss = User::factory()->bossPrincipal()->create();
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'sale_price' => '20.00',
            'purchase_price' => '8.00',
        ]);
        app(InventoryService::class)->receive(
            $boss,
            $product,
            app(LocationProvisioner::class)->boutique($boss->organization),
            5,
            'Stock',
        );

        return [$boss, $product];
    }
}
