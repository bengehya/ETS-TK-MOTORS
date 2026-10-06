<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\CashEntry;
use App\Models\CustomerRequest;
use App\Models\Expense;
use App\Models\Rental;
use App\Models\Sale;
use Carbon\CarbonInterface;

class OperationsPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function sale(Sale $sale, bool $includeFinance): array
    {
        $payload = [
            'id' => $sale->id,
            'reference' => $sale->reference,
            'quantity' => $sale->quantity,
            'unit_sale_price' => Money::normalize($sale->unit_sale_price),
            'line_total' => Money::normalize($sale->line_total),
            'status' => $sale->status->value,
            'status_label' => $sale->status->label(),
            'sold_at' => self::iso($sale->sold_at),
            'sold_at_label' => self::label($sale->sold_at),
            'cancelled_at' => self::iso($sale->cancelled_at),
            'cancelled_at_label' => self::label($sale->cancelled_at),
            'cancellation_reason' => $sale->cancellation_reason,
            'product' => $sale->product ? [
                'id' => $sale->product->id,
                'code' => $sale->product->code,
                'name' => $sale->product->name,
                'barcode' => $sale->product->barcode,
            ] : null,
            'location' => $sale->location ? [
                'id' => $sale->location->id,
                'name' => $sale->location->name,
            ] : null,
            'seller' => UserPresenter::identity($sale->seller),
            'canceller' => UserPresenter::identity($sale->canceller),
        ];

        if ($includeFinance) {
            $payload['unit_purchase_cost'] = Money::normalize($sale->unit_purchase_cost);
            $payload['cost_total'] = Money::normalize($sale->cost_total);
            $payload['profit'] = Money::normalize($sale->profit);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function cashEntry(CashEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'reference' => $entry->reference,
            'direction' => $entry->direction->value,
            'direction_label' => $entry->direction->label(),
            'amount' => Money::normalize($entry->amount),
            'balance_before' => Money::normalize($entry->balance_before),
            'balance_after' => Money::normalize($entry->balance_after),
            'label' => $entry->label,
            'occurred_at' => self::iso($entry->occurred_at),
            'occurred_at_label' => self::label($entry->occurred_at),
            'user' => UserPresenter::identity($entry->user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function expense(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'reference' => $expense->reference,
            'amount' => Money::normalize($expense->amount),
            'reason' => $expense->reason,
            'spent_on' => $expense->spent_on?->toDateString(),
            'spent_on_label' => $expense->spent_on?->timezone(config('app.timezone'))->format('d/m/Y'),
            'status' => $expense->status->value,
            'status_label' => $expense->status->label(),
            'decision_note' => $expense->decision_note,
            'decided_at_label' => self::label($expense->decided_at),
            'creator' => UserPresenter::identity($expense->creator),
            'decider' => UserPresenter::identity($expense->decider),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function customerRequest(CustomerRequest $request): array
    {
        return [
            'id' => $request->id,
            'customer_name' => $request->customer_name,
            'quantity' => $request->quantity,
            'priority' => $request->priority->value,
            'priority_label' => $request->priority->label(),
            'frequency' => $request->frequency,
            'status' => $request->status->value,
            'status_label' => $request->status->label(),
            'notes' => $request->notes,
            'closure_note' => $request->closure_note,
            'requested_at' => self::iso($request->requested_at),
            'requested_at_label' => self::label($request->requested_at),
            'closed_at_label' => self::label($request->closed_at),
            'product' => $request->product ? [
                'id' => $request->product->id,
                'code' => $request->product->code,
                'name' => $request->product->name,
            ] : null,
            'recorder' => UserPresenter::identity($request->recorder),
            'closer' => UserPresenter::identity($request->closer),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rental(Rental $rental, bool $includeAmount): array
    {
        $payload = [
            'id' => $rental->id,
            'label' => $rental->label,
            'started_on' => $rental->started_on?->toDateString(),
            'started_on_label' => $rental->started_on?->timezone(config('app.timezone'))->format('d/m/Y'),
            'duration_months' => $rental->duration_months,
            'ends_on' => $rental->ends_on?->toDateString(),
            'ends_on_label' => $rental->ends_on?->timezone(config('app.timezone'))->format('d/m/Y'),
            'remaining_months' => $rental->remainingMonths(),
            'status' => $rental->status->value,
            'status_label' => $rental->status->label(),
            'closure_reason' => $rental->closure_reason,
            'closed_at_label' => self::label($rental->closed_at),
            'recorder' => UserPresenter::identity($rental->recorder),
            'closer' => UserPresenter::identity($rental->closer),
        ];

        if ($includeAmount) {
            $payload['amount'] = Money::normalize($rental->amount);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function audit(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'action' => $log->action,
            'reason' => $log->reason,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'created_at' => self::iso($log->created_at),
            'created_at_label' => self::label($log->created_at),
            'user' => UserPresenter::identity($log->user),
            'subject_type' => $log->subject_type ? class_basename($log->subject_type) : null,
            'subject_id' => $log->subject_id,
        ];
    }

    private static function iso(?CarbonInterface $value): ?string
    {
        return $value?->timezone(config('app.timezone'))->toIso8601String();
    }

    private static function label(?CarbonInterface $value): ?string
    {
        return $value?->timezone(config('app.timezone'))->format('d/m/Y H:i');
    }
}
