<?php

namespace Tests\Feature\Requests;

use App\Enums\CustomerRequestPriority;
use App\Enums\CustomerRequestStatus;
use App\Models\CustomerRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\CustomerRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_requests_raise_frequency_and_priority(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create(['organization_id' => $boss->organization_id]);
        $product = Product::factory()->create([
            'organization_id' => $boss->organization_id,
            'name' => 'Courroie',
            'barcode' => 'CRR-100',
        ]);
        $service = app(CustomerRequestService::class);

        $first = $service->record($employee, $product, 'Jean', 1, false, null);
        $second = $service->record($employee, $product, 'Aline', null, false, null);
        $third = $service->record($boss, $product, null, 2, false, 'Toujours indisponible');

        $this->assertSame(1, $first->frequency);
        $this->assertSame(CustomerRequestPriority::Normal, $first->priority);
        $this->assertSame(2, $second->frequency);
        $this->assertSame(CustomerRequestPriority::Normal, $second->priority);
        $this->assertSame(3, $third->frequency);
        $this->assertSame(CustomerRequestPriority::Urgent, $third->priority);
        $this->assertSame($employee->id, $first->recorded_by);

        $urgent = $service->record($employee, $product, 'Paul', 1, true, null);
        $this->assertSame(CustomerRequestPriority::Urgent, $urgent->priority);

        $this->actingAs($employee)
            ->get(route('requests.index', ['q' => 'CRR-100', 'priority' => 'urgent']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('requests.data', 1));

        $this->actingAs($employee)
            ->post(route('requests.fulfill', $first), ['note' => 'Pièce reçue'])
            ->assertForbidden();

        $this->actingAs($boss)
            ->post(route('requests.fulfill', $first), ['note' => 'Pièce reçue'])
            ->assertRedirect(route('requests.show', $first));

        $this->assertSame(CustomerRequestStatus::Fulfilled, $first->refresh()->status);
        $this->assertSame(4, CustomerRequest::query()->count());

        $this->actingAs($boss)
            ->post(route('requests.cancel', $second), ['note' => 'Client parti'])
            ->assertRedirect();

        $this->assertSame(CustomerRequestStatus::Cancelled, $second->refresh()->status);

        $this->actingAs($boss)
            ->post(route('requests.cancel', $second), ['note' => 'Deuxième fois'])
            ->assertSessionHasErrors('note');
    }

    public function test_a_guest_cannot_record_a_request(): void
    {
        $this->get(route('requests.index'))->assertRedirect(route('login'));
    }
}
