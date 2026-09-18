<?php

namespace App\Services;

use App\Enums\ArrivalStatus;
use App\Exceptions\ArrivalAlreadyProcessedException;
use App\Exceptions\InactiveProductException;
use App\Models\Arrival;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ArrivalService
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function record(
        User $user,
        Product $product,
        Location $location,
        int $quantity,
        ?string $supplierReference = null,
    ): Arrival {
        if ($user->organization_id !== $product->organization_id || $user->organization_id !== $location->organization_id) {
            throw new InvalidArgumentException('Les données n’appartiennent pas à la même organisation.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('La quantité doit être un entier positif.');
        }

        if (! $product->is_active) {
            throw new InactiveProductException('Cet article est désactivé.');
        }

        $reference = trim((string) $supplierReference);

        return Arrival::query()->create([
            'organization_id' => $user->organization_id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => $quantity,
            'supplier_reference' => $reference === '' ? null : $reference,
            'recorded_by' => $user->id,
            'status' => ArrivalStatus::Pending,
        ]);
    }

    public function approve(User $user, Arrival $arrival): Arrival
    {
        try {
            return DB::transaction(function () use ($user, $arrival): Arrival {
                /** @var Arrival $locked */
                $locked = Arrival::query()
                    ->whereKey($arrival->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $locked->isPending()) {
                    throw new ArrivalAlreadyProcessedException('Cet arrivage a déjà été traité.');
                }

                $locked->load(['product', 'location']);

                $movement = $this->inventory->receive(
                    $user,
                    $locked->product,
                    $locked->location,
                    $locked->quantity,
                    $this->validationNote($locked),
                    Arrival::class,
                    $locked->id,
                );

                $locked->forceFill([
                    'status' => ArrivalStatus::Validated,
                    'validated_by' => $user->id,
                    'validated_at' => now(),
                    'stock_movement_id' => $movement->id,
                ])->save();

                return $locked->refresh()->load(['product', 'location', 'recorder', 'validator', 'stockMovement']);
            });
        } catch (InactiveProductException $exception) {
            throw $exception;
        }
    }

    public function reject(User $user, Arrival $arrival, string $reason): Arrival
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour rejeter un arrivage.');
        }

        return DB::transaction(function () use ($user, $arrival, $reason): Arrival {
            /** @var Arrival $locked */
            $locked = Arrival::query()
                ->whereKey($arrival->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isPending()) {
                throw new ArrivalAlreadyProcessedException('Cet arrivage a déjà été traité.');
            }

            $locked->forceFill([
                'status' => ArrivalStatus::Rejected,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return $locked->refresh()->load(['product', 'location', 'recorder', 'rejector']);
        });
    }

    private function validationNote(Arrival $arrival): string
    {
        $note = 'Arrivage validé';

        if (filled($arrival->supplier_reference)) {
            $note .= ' — réf. '.$arrival->supplier_reference;
        }

        return $note;
    }
}
