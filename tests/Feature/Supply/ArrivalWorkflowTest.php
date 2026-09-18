<?php

namespace Tests\Feature\Supply;

use App\Enums\ArrivalStatus;
use App\Enums\StockMovementType;
use App\Models\Arrival;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\LocationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArrivalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_can_record_an_arrival_without_increasing_stock(): void
    {
        [$boss, $employee, $product, $depot] = $this->setupCatalog();

        $this->actingAs($employee)->post('/arrivages', [
            'product_id' => $product->id,
            'location_id' => $depot->id,
            'quantity' => 5,
            'supplier_reference' => 'BL-44',
        ])->assertRedirect();

        $arrival = Arrival::query()->first();

        $this->assertNotNull($arrival);
        $this->assertSame(ArrivalStatus::Pending, $arrival->status);
        $this->assertSame($employee->id, $arrival->recorded_by);
        $this->assertSame('BL-44', $arrival->supplier_reference);
        $this->assertSame(0, $this->quantity($product, $depot->id));
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_an_employee_cannot_approve_an_arrival(): void
    {
        [$boss, $employee, $product, $depot] = $this->setupCatalog();
        $arrival = $this->pendingArrival($employee, $product, $depot->id, 3);

        $this->actingAs($employee)
            ->post('/arrivages/'.$arrival->id.'/valider')
            ->assertForbidden();

        $this->assertSame(ArrivalStatus::Pending, $arrival->fresh()->status);
        $this->assertSame(0, $this->quantity($product, $depot->id));
    }

    public function test_a_boss_can_approve_an_arrival_which_increases_stock_and_creates_a_movement(): void
    {
        [$boss, $employee, $product, $depot] = $this->setupCatalog();
        $arrival = $this->pendingArrival($employee, $product, $depot->id, 5);

        $this->actingAs($boss)
            ->post('/arrivages/'.$arrival->id.'/valider')
            ->assertRedirect();

        $arrival->refresh();

        $this->assertSame(ArrivalStatus::Validated, $arrival->status);
        $this->assertSame($employee->id, $arrival->recorded_by);
        $this->assertSame($boss->id, $arrival->validated_by);
        $this->assertNotNull($arrival->validated_at);
        $this->assertSame(5, $this->quantity($product, $depot->id));

        $movement = StockMovement::query()->first();
        $this->assertNotNull($movement);
        $this->assertSame(StockMovementType::Receipt, $movement->type);
        $this->assertSame($boss->id, $movement->user_id);
        $this->assertSame($product->id, $movement->product_id);
        $this->assertSame($depot->id, $movement->location_id);
        $this->assertSame(5, $movement->quantity);
        $this->assertSame(Arrival::class, $movement->reference_type);
        $this->assertSame($arrival->id, $movement->reference_id);
    }

    public function test_a_secondary_boss_can_approve_an_arrival(): void
    {
        [$boss, $employee, $product, $depot] = $this->setupCatalog();
        $secondary = User::factory()->bossSecondaire()->create([
            'organization_id' => $boss->organization_id,
        ]);
        $arrival = $this->pendingArrival($employee, $product, $depot->id, 2);

        $this->actingAs($secondary)
            ->post('/arrivages/'.$arrival->id.'/valider')
            ->assertRedirect();

        $this->assertSame(ArrivalStatus::Validated, $arrival->fresh()->status);
        $this->assertSame($secondary->id, $arrival->fresh()->validated_by);
        $this->assertSame(2, $this->quantity($product, $depot->id));
    }

    public function test_an_arrival_cannot_be_approved_twice(): void
    {
        [$boss, $employee, $product, $depot] = $this->setupCatalog();
        $arrival = $this->pendingArrival($employee, $product, $depot->id, 4);

        $this->actingAs($boss)->post('/arrivages/'.$arrival->id.'/valider')->assertRedirect();
        $this->actingAs($boss)->post('/arrivages/'.$arrival->id.'/valider')->assertSessionHasErrors('arrival');

        $this->assertSame(4, $this->quantity($product, $depot->id));
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_a_rejected_arrival_does_not_increase_stock_and_keeps_its_history(): void
    {
        [$boss, $employee, $product, $depot] = $this->setupCatalog();
        $arrival = $this->pendingArrival($employee, $product, $depot->id, 7);

        $this->actingAs($boss)->post('/arrivages/'.$arrival->id.'/rejeter', [
            'rejection_reason' => 'Colis endommagé à la réception',
        ])->assertRedirect();

        $arrival->refresh();

        $this->assertSame(ArrivalStatus::Rejected, $arrival->status);
        $this->assertSame($employee->id, $arrival->recorded_by);
        $this->assertSame($boss->id, $arrival->rejected_by);
        $this->assertSame('Colis endommagé à la réception', $arrival->rejection_reason);
        $this->assertSame(0, $this->quantity($product, $depot->id));
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseHas('arrivals', ['id' => $arrival->id]);
    }

    public function test_a_rejected_arrival_cannot_later_be_approved(): void
    {
        [$boss, $employee, $product, $depot] = $this->setupCatalog();
        $arrival = $this->pendingArrival($employee, $product, $depot->id, 3);

        $this->actingAs($boss)->post('/arrivages/'.$arrival->id.'/rejeter', [
            'rejection_reason' => 'Référence fournisseur incorrecte',
        ])->assertRedirect();

        $this->actingAs($boss)->post('/arrivages/'.$arrival->id.'/valider')->assertSessionHasErrors('arrival');
        $this->assertSame(0, $this->quantity($product, $depot->id));
    }

    public function test_an_employee_can_view_only_their_own_arrivals(): void
    {
        [$boss, $employee, $product, $depot] = $this->setupCatalog();
        $other = User::factory()->employe()->create([
            'organization_id' => $boss->organization_id,
        ]);
        $own = $this->pendingArrival($employee, $product, $depot->id, 1);
        $foreign = $this->pendingArrival($other, $product, $depot->id, 2);

        $this->actingAs($employee)
            ->get('/arrivages')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Supply/Arrivals/Index')
                ->has('arrivals.data', 1)
                ->where('arrivals.data.0.id', $own->id)
            );

        $this->actingAs($employee)->get('/arrivages/'.$foreign->id)->assertForbidden();
        $this->actingAs($boss)->get('/arrivages/'.$foreign->id)->assertOk();
    }

    public function test_guests_cannot_access_arrivals(): void
    {
        $this->get('/arrivages')->assertRedirect(route('login'));
        $this->post('/arrivages')->assertRedirect(route('login'));
    }

    /**
     * @return array{0: User, 1: User, 2: Product, 3: \App\Models\Location}
     */
    private function setupCatalog(): array
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create([
            'organization_id' => $boss->organization_id,
        ]);
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
        ]);
        $depot = app(LocationProvisioner::class)->depot($boss->organization);

        return [$boss, $employee, $product, $depot];
    }

    private function pendingArrival(User $recorder, Product $product, int $locationId, int $quantity): Arrival
    {
        return Arrival::query()->create([
            'organization_id' => $recorder->organization_id,
            'product_id' => $product->id,
            'location_id' => $locationId,
            'quantity' => $quantity,
            'recorded_by' => $recorder->id,
            'status' => ArrivalStatus::Pending,
        ]);
    }

    private function quantity(Product $product, int $locationId): int
    {
        return (int) Inventory::query()
            ->where('product_id', $product->id)
            ->where('location_id', $locationId)
            ->value('quantity');
    }
}
