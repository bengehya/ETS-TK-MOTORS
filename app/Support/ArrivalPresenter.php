<?php

namespace App\Support;

use App\Models\Arrival;

class ArrivalPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function arrival(Arrival $arrival): array
    {
        return [
            'id' => $arrival->id,
            'quantity' => $arrival->quantity,
            'supplier_reference' => $arrival->supplier_reference,
            'status' => $arrival->status->value,
            'status_label' => $arrival->status->label(),
            'created_at' => $arrival->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'validated_at' => $arrival->validated_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'rejected_at' => $arrival->rejected_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'rejection_reason' => $arrival->rejection_reason,
            'product' => $arrival->relationLoaded('product') && $arrival->product ? [
                'id' => $arrival->product->id,
                'code' => $arrival->product->code,
                'name' => $arrival->product->name,
                'is_active' => $arrival->product->is_active,
            ] : null,
            'location' => $arrival->relationLoaded('location') && $arrival->location
                ? CatalogPresenter::location($arrival->location)
                : null,
            'recorder' => $arrival->relationLoaded('recorder') && $arrival->recorder ? [
                'id' => $arrival->recorder->id,
                'name' => $arrival->recorder->name,
            ] : null,
            'validator' => $arrival->relationLoaded('validator') && $arrival->validator ? [
                'id' => $arrival->validator->id,
                'name' => $arrival->validator->name,
            ] : null,
            'rejector' => $arrival->relationLoaded('rejector') && $arrival->rejector ? [
                'id' => $arrival->rejector->id,
                'name' => $arrival->rejector->name,
            ] : null,
        ];
    }
}
