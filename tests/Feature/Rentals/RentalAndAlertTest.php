<?php

namespace Tests\Feature\Rentals;

use App\Enums\RentalStatus;
use App\Exceptions\OperationAlreadyProcessedException;
use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use App\Services\ArrivalService;
use App\Services\CustomerRequestService;
use App\Services\InventoryService;
use App\Services\LocationProvisioner;
use App\Services\RentalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RentalAndAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_rental_calculates_its_end_and_can_be_closed_once(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $start = now()->startOfDay();
        $rental = app(RentalService::class)->open($boss, 'Local vitrine', '1500.00', $start->toDateString(), 12);

        $this->assertSame($start->addMonths(12)->toDateString(), $rental->ends_on?->toDateString());
        $this->assertSame(RentalStatus::Active, $rental->status);
        $this->assertGreaterThan(0, $rental->remainingMonths());

        $this->actingAs($boss)->get(route('rentals.index'))->assertNotFound();
        $this->actingAs($boss)
            ->post(route('rentals.close', $rental), ['reason' => 'Fin anticipée'])
            ->assertNotFound();
        $this->assertSame(RentalStatus::Active, $rental->refresh()->status);

        app(RentalService::class)->close($boss, $rental, 'Fin anticipée');

        $this->assertSame(RentalStatus::Closed, $rental->refresh()->status);
        $this->assertSame(1, Rental::query()->count());

        $this->expectException(OperationAlreadyProcessedException::class);
        app(RentalService::class)->close($boss, $rental->refresh(), 'Encore');
    }

    public function test_an_overdue_rental_becomes_expired_without_being_deleted(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $rental = app(RentalService::class)->open(
            $boss,
            'Hangar',
            '800.00',
            now()->subMonths(3)->toDateString(),
            1,
        );

        $this->actingAs($boss)->get(route('rentals.show', $rental))->assertNotFound();

        app(RentalService::class)->syncExpired($boss);

        $this->assertSame(RentalStatus::Expired, $rental->refresh()->status);
        $this->assertSame(1, Rental::query()->count());
    }

    public function test_alerts_come_from_real_conditions_and_hide_amounts_from_employees(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create(['organization_id' => $boss->organization_id]);
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'name' => 'Filtre à air',
            'sale_price' => '12.00',
            'purchase_price' => '4.00',
        ]);
        $locations = app(LocationProvisioner::class);
        app(InventoryService::class)->receive($boss, $product, $locations->boutique($boss->organization), 1, 'Seuil');

        $old = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'name' => 'Rétroviseur dormant',
            'created_at' => now()->subDays(40),
        ]);
        app(InventoryService::class)->receive($boss, $old, $locations->boutique($boss->organization), 2, 'Dormant');
        $old->forceFill(['created_at' => now()->subDays(40)])->save();

        $requests = app(CustomerRequestService::class);
        $requests->record($employee, $product, 'A', 1, false, null);
        $requests->record($employee, $product, 'B', 1, false, null);
        $requests->record($employee, $product, 'C', 1, true, null);

        app(ArrivalService::class)->record($employee, $product, $locations->depot($boss->organization), 2, 'FOURN-1');

        app(RentalService::class)->open($boss, 'Local vitrine', '1500.00', now()->subDays(5)->toDateString(), 1);

        $this->actingAs($boss)
            ->get(route('alerts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('alerts')
                ->where('alerts', function ($alerts): bool {
                    $types = collect($alerts)->pluck('type');

                    return $types->contains('low_stock')
                        && $types->contains('high_demand')
                        && $types->contains('low_demand')
                        && $types->contains('repeated_request')
                        && $types->contains('urgent_request')
                        && $types->contains('pending_arrival')
                        && ! $types->contains('rental_expiration');
                })
            );
        $this->assertStringNotContainsString('Locations', $this->actingAs($boss)->get(route('dashboard'))->getContent() ?? '');

        $employeePage = $this->actingAs($employee)->get(route('alerts.index'));
        $employeePage->assertOk();
        $employeePage->assertInertia(fn (Assert $page) => $page
            ->where('alerts', function ($alerts): bool {
                return collect($alerts)->firstWhere('type', 'rental_expiration') === null;
            })
        );
        $this->assertStringNotContainsString('1500.00', $employeePage->getContent());
    }
}
