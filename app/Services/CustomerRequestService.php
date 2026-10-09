<?php

namespace App\Services;

use App\Enums\CustomerRequestPriority;
use App\Enums\CustomerRequestStatus;
use App\Enums\Permission;
use App\Enums\RestockSuggestionStatus;
use App\Exceptions\InactiveProductException;
use App\Exceptions\OperationAlreadyProcessedException;
use App\Models\CustomerRequest;
use App\Models\CustomerRequestEvent;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\RestockSuggestion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomerRequestService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly LocationProvisioner $locations,
    ) {}

    public function record(
        User $user,
        ?Product $product,
        ?string $customerName,
        ?int $quantity,
        bool $urgent,
        ?string $notes,
        ?string $designation = null,
    ): CustomerRequest {
        if (! $user->hasPermission(Permission::CreateCustomerRequests)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        $designation = $this->blankToNull($designation);
        $designationKey = $this->designationKey($designation);

        if ($product === null && $designationKey === null) {
            throw new InvalidArgumentException('Un article ou une désignation est obligatoire.');
        }

        if ($product !== null && $user->organization_id !== $product->organization_id) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }

        if ($product !== null && ! $product->is_active) {
            throw new InactiveProductException('Cet article est désactivé.');
        }

        if ($quantity !== null && $quantity < 1) {
            throw new InvalidArgumentException('La quantité doit être un entier positif.');
        }

        $customerName = $this->blankToNull($customerName);
        $notes = $this->blankToNull($notes);

        return DB::transaction(function () use ($user, $product, $customerName, $quantity, $urgent, $notes, $designation, $designationKey): CustomerRequest {
            $matching = CustomerRequest::query()->where('organization_id', $user->organization_id);
            $this->constrainSubject($matching, $product, $designationKey);

            $existing = (clone $matching)->count();
            $open = (clone $matching)->where('status', CustomerRequestStatus::Open)->count();

            $frequency = $existing + 1;
            $priority = ($urgent || ($open + 1) >= 3)
                ? CustomerRequestPriority::Urgent
                : CustomerRequestPriority::Normal;

            $request = CustomerRequest::query()->create([
                'organization_id' => $user->organization_id,
                'product_id' => $product?->id,
                'designation' => $product === null ? $designation : null,
                'designation_key' => $product === null ? $designationKey : null,
                'recorded_by' => $user->id,
                'customer_name' => $customerName,
                'quantity' => $quantity,
                'priority' => $priority,
                'frequency' => $frequency,
                'status' => CustomerRequestStatus::Open,
                'notes' => $notes,
                'requested_at' => now(),
            ]);

            $this->event($request, $user, 'created', null, CustomerRequestStatus::Open->value, $notes);

            $this->audit->record($user, 'customer_request.created', $request, null, [
                'product_id' => $product?->id,
                'designation' => $request->designation,
                'quantity' => $quantity,
                'priority' => $priority->value,
                'frequency' => $frequency,
                'customer_name' => $customerName,
            ]);

            $this->refreshSuggestion($user, $product, $designation, $designationKey);

            return $request->fresh(['product', 'recorder', 'events.user']);
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
        if (! $user->hasPermission(Permission::ManageSales)) {
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

            $this->event($locked, $user, $action, CustomerRequestStatus::Open->value, $status->value, $note);

            $this->audit->record($user, $action, $locked, [
                'status' => CustomerRequestStatus::Open->value,
            ], [
                'status' => $status->value,
                'product_id' => $locked->product_id,
                'designation' => $locked->designation,
            ], $note);

            return $locked->fresh(['product', 'recorder', 'closer', 'events.user']);
        });
    }

    public function decideSuggestion(User $user, RestockSuggestion $suggestion, RestockSuggestionStatus $status, ?string $justification): RestockSuggestion
    {
        if (! $user->hasPermission(Permission::ManageSales)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        if ($user->organization_id !== $suggestion->organization_id) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }

        if ($status === RestockSuggestionStatus::Open) {
            throw new InvalidArgumentException('Une suggestion ne peut pas être remise au statut initial.');
        }

        $justification = $this->blankToNull($justification);

        if (in_array($status, [RestockSuggestionStatus::Rejected, RestockSuggestionStatus::Decided], true) && $justification === null) {
            throw new InvalidArgumentException('Une justification est obligatoire pour ce statut.');
        }

        $previous = $suggestion->status->value;

        $suggestion->forceFill([
            'status' => $status,
            'justification' => $justification,
            'decided_by' => $user->id,
            'decided_at' => now(),
        ])->save();

        $this->audit->record($user, 'restock_suggestion.updated', $suggestion, [
            'status' => $previous,
        ], [
            'status' => $status->value,
            'product_id' => $suggestion->product_id,
            'designation' => $suggestion->designation,
        ], $justification);

        return $suggestion->fresh(['product', 'decider']);
    }

    private function refreshSuggestion(User $user, ?Product $product, ?string $designation, ?string $designationKey): void
    {
        $days = (int) config('tkmotors.restock.observation_days', 30);
        $threshold = (int) config('tkmotors.restock.repeat_threshold', 3);
        $since = now()->subDays($days);

        $window = CustomerRequest::query()
            ->where('organization_id', $user->organization_id)
            ->where('requested_at', '>=', $since);
        $this->constrainSubject($window, $product, $designationKey);

        $count = (clone $window)->count();

        if ($count < $threshold) {
            return;
        }

        $subjectKey = $product !== null ? 'product:'.$product->id : 'designation:'.$designationKey;
        $totalQuantity = (int) (clone $window)->sum('quantity');
        $lastRequestedAt = (clone $window)->max('requested_at');
        $hasUrgent = (clone $window)->where('priority', CustomerRequestPriority::Urgent)->exists();
        $priority = $hasUrgent || $count >= 3
            ? CustomerRequestPriority::Urgent
            : CustomerRequestPriority::Normal;

        [$boutiqueQuantity, $depotQuantity] = $this->stockSnapshot($user, $product);

        $existing = RestockSuggestion::query()
            ->where('organization_id', $user->organization_id)
            ->where('subject_key', $subjectKey)
            ->whereIn('status', [RestockSuggestionStatus::Open, RestockSuggestionStatus::Reviewed])
            ->lockForUpdate()
            ->first();

        $payload = [
            'product_id' => $product?->id,
            'designation' => $product === null ? $designation : null,
            'request_count' => $count,
            'total_quantity' => $totalQuantity,
            'observation_days' => $days,
            'period_started_at' => $since,
            'last_requested_at' => $lastRequestedAt,
            'boutique_quantity' => $boutiqueQuantity,
            'depot_quantity' => $depotQuantity,
            'priority' => $priority,
        ];

        if ($existing !== null) {
            $existing->forceFill($payload)->save();

            return;
        }

        $suggestion = RestockSuggestion::query()->create([
            ...$payload,
            'organization_id' => $user->organization_id,
            'subject_key' => $subjectKey,
            'status' => RestockSuggestionStatus::Open,
        ]);

        $this->audit->record($user, 'restock_suggestion.created', $suggestion, null, [
            'subject_key' => $subjectKey,
            'request_count' => $count,
            'total_quantity' => $totalQuantity,
            'boutique_quantity' => $boutiqueQuantity,
            'depot_quantity' => $depotQuantity,
            'priority' => $priority->value,
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function stockSnapshot(User $user, ?Product $product): array
    {
        if ($product === null) {
            return [0, 0];
        }

        $organization = $user->organization;
        $boutique = $this->locations->boutique($organization);
        $depot = $this->locations->depot($organization);

        $boutiqueQuantity = (int) Inventory::query()
            ->where('product_id', $product->id)
            ->where('location_id', $boutique->id)
            ->value('quantity');
        $depotQuantity = (int) Inventory::query()
            ->where('product_id', $product->id)
            ->where('location_id', $depot->id)
            ->value('quantity');

        return [$boutiqueQuantity, $depotQuantity];
    }

    private function constrainSubject($query, ?Product $product, ?string $designationKey): void
    {
        if ($product !== null) {
            $query->where('product_id', $product->id);

            return;
        }

        $query->whereNull('product_id')->where('designation_key', $designationKey);
    }

    private function event(CustomerRequest $request, User $user, string $action, ?string $from, ?string $to, ?string $note): void
    {
        CustomerRequestEvent::query()->create([
            'customer_request_id' => $request->id,
            'user_id' => $user->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
        ]);
    }

    private function designationKey(?string $designation): ?string
    {
        $value = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) $designation)));

        return $value === '' ? null : $value;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
