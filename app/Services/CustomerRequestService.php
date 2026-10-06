<?php

namespace App\Services;

use App\Enums\CustomerRequestPriority;
use App\Enums\CustomerRequestStatus;
use App\Enums\Permission;
use App\Exceptions\InactiveProductException;
use App\Exceptions\OperationAlreadyProcessedException;
use App\Models\CustomerRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomerRequestService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function record(
        User $user,
        Product $product,
        ?string $customerName,
        ?int $quantity,
        bool $urgent,
        ?string $notes,
    ): CustomerRequest {
        if (! $user->hasPermission(Permission::CreateCustomerRequests)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        if ($user->organization_id !== $product->organization_id) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }

        if (! $product->is_active) {
            throw new InactiveProductException('Cet article est désactivé.');
        }

        if ($quantity !== null && $quantity < 1) {
            throw new InvalidArgumentException('La quantité doit être un entier positif.');
        }

        $customerName = $this->blankToNull($customerName);
        $notes = $this->blankToNull($notes);

        return DB::transaction(function () use ($user, $product, $customerName, $quantity, $urgent, $notes): CustomerRequest {
            $existing = CustomerRequest::query()
                ->where('organization_id', $user->organization_id)
                ->where('product_id', $product->id)
                ->count();

            $open = CustomerRequest::query()
                ->where('organization_id', $user->organization_id)
                ->where('product_id', $product->id)
                ->where('status', CustomerRequestStatus::Open)
                ->count();

            $frequency = $existing + 1;
            $priority = ($urgent || ($open + 1) >= 3)
                ? CustomerRequestPriority::Urgent
                : CustomerRequestPriority::Normal;

            $request = CustomerRequest::query()->create([
                'organization_id' => $user->organization_id,
                'product_id' => $product->id,
                'recorded_by' => $user->id,
                'customer_name' => $customerName,
                'quantity' => $quantity,
                'priority' => $priority,
                'frequency' => $frequency,
                'status' => CustomerRequestStatus::Open,
                'notes' => $notes,
                'requested_at' => now(),
            ]);

            $this->audit->record($user, 'customer_request.created', $request, null, [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'priority' => $priority->value,
                'frequency' => $frequency,
                'customer_name' => $customerName,
            ]);

            return $request->fresh(['product', 'recorder']);
        });
    }

    public function fulfill(User $user, CustomerRequest $request, ?string $note): CustomerRequest
    {
        return $this->close($user, $request, CustomerRequestStatus::Fulfilled, 'customer_request.fulfilled', $note);
    }

    public function cancel(User $user, CustomerRequest $request, string $note): CustomerRequest
    {
        $note = trim($note);

        if ($note === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour annuler une demande.');
        }

        return $this->close($user, $request, CustomerRequestStatus::Cancelled, 'customer_request.cancelled', $note);
    }

    private function close(
        User $user,
        CustomerRequest $request,
        CustomerRequestStatus $status,
        string $action,
        ?string $note,
    ): CustomerRequest {
        if (! $user->hasPermission(Permission::CreateCustomerRequests)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        if ($user->organization_id !== $request->organization_id) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }

        $note = $this->blankToNull($note);

        return DB::transaction(function () use ($user, $request, $status, $action, $note): CustomerRequest {
            /** @var CustomerRequest $locked */
            $locked = CustomerRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw new OperationAlreadyProcessedException('Cette demande a déjà été traitée.');
            }

            $locked->forceFill([
                'status' => $status,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'closure_note' => $note,
            ])->save();

            $this->audit->record($user, $action, $locked, [
                'status' => CustomerRequestStatus::Open->value,
            ], [
                'status' => $status->value,
                'product_id' => $locked->product_id,
            ], $note);

            return $locked->fresh(['product', 'recorder', 'closer']);
        });
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
