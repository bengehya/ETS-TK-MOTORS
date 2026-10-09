<?php

namespace App\Support;

use App\Enums\Currency;
use App\Models\AuditLog;
use App\Models\CashDeclaration;
use App\Models\CashEntry;
use App\Models\CustomerRequest;
use App\Models\CustomerRequestEvent;
use App\Models\Exchange;
use App\Models\ExchangeRate;
use App\Models\Expense;
use App\Models\Rental;
use App\Models\RestockSuggestion;
use App\Models\Sale;
use App\Models\SaleLine;
use Carbon\CarbonInterface;

class OperationsPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function sale(Sale $sale, bool $includeFinance): array
    {
        $currency = $sale->currency instanceof Currency ? $sale->currency->value : (string) ($sale->currency ?? Currency::Usd->value);
        $sale->loadMissing('lines.product');

        $payload = [
            'id' => $sale->id,
            'reference' => $sale->reference,
            'currency' => $currency,
            'quantity' => $sale->quantity,
            'unit_sale_price' => Money::normalize($sale->unit_sale_price),
            'line_total' => Money::normalize($sale->line_total),
            'amount_received' => Money::normalize($sale->amount_received ?? $sale->line_total),
            'change_given' => Money::normalize($sale->change_given),
            'lines' => $sale->lines->map(fn (SaleLine $line): array => self::saleLine($line, $includeFinance))->values()->all(),
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
    private static function saleLine(SaleLine $line, bool $includeFinance): array
    {
        $payload = [
            'id' => $line->id,
            'quantity' => $line->quantity,
            'unit_sale_price' => Money::normalize($line->unit_sale_price),
            'line_total' => Money::normalize($line->line_total),
            'product' => $line->product ? [
                'id' => $line->product->id,
                'code' => $line->product->code,
                'name' => $line->product->name,
                'barcode' => $line->product->barcode,
            ] : null,
        ];

        if ($includeFinance) {
            $payload['unit_purchase_cost'] = Money::normalize($line->unit_purchase_cost);
            $payload['cost_total'] = Money::normalize($line->cost_total);
            $payload['profit'] = Money::normalize($line->profit);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function cashEntry(CashEntry $entry): array
    {
        $currency = $entry->currency instanceof Currency ? $entry->currency->value : (string) ($entry->currency ?? Currency::Usd->value);

        return [
            'id' => $entry->id,
            'reference' => $entry->reference,
            'direction' => $entry->direction->value,
            'direction_label' => $entry->direction->label(),
            'currency' => $currency,
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
            'currency' => $expense->currency instanceof Currency ? $expense->currency->value : Currency::Usd->value,
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
            'designation' => $request->designation,
            'label' => $request->product?->name ?? $request->designation,
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
            'events' => $request->relationLoaded('events')
                ? $request->events->map(fn (CustomerRequestEvent $event): array => [
                    'id' => $event->id,
                    'action' => $event->action,
                    'from_status' => $event->from_status,
                    'to_status' => $event->to_status,
                    'note' => $event->note,
                    'created_at_label' => self::label($event->created_at),
                    'user' => UserPresenter::identity($event->user),
                ])->values()->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function restockSuggestion(RestockSuggestion $suggestion): array
    {
        return [
            'id' => $suggestion->id,
            'designation' => $suggestion->designation,
            'label' => $suggestion->product?->name ?? $suggestion->designation,
            'request_count' => $suggestion->request_count,
            'total_quantity' => $suggestion->total_quantity,
            'observation_days' => $suggestion->observation_days,
            'last_requested_at_label' => self::label($suggestion->last_requested_at),
            'boutique_quantity' => $suggestion->boutique_quantity,
            'depot_quantity' => $suggestion->depot_quantity,
            'priority' => $suggestion->priority->value,
            'priority_label' => $suggestion->priority->label(),
            'status' => $suggestion->status->value,
            'status_label' => $suggestion->status->label(),
            'justification' => $suggestion->justification,
            'decided_at_label' => self::label($suggestion->decided_at),
            'product' => $suggestion->product ? [
                'id' => $suggestion->product->id,
                'code' => $suggestion->product->code,
                'name' => $suggestion->product->name,
            ] : null,
            'decider' => UserPresenter::identity($suggestion->decider),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function exchangeRate(ExchangeRate $rate): array
    {
        return [
            'id' => $rate->id,
            'cdf_per_usd' => Money::normalizeRate($rate->cdf_per_usd),
            'effective_at_label' => self::label($rate->effective_at),
            'creator' => UserPresenter::identity($rate->creator),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function exchange(Exchange $exchange): array
    {
        return [
            'id' => $exchange->id,
            'reference' => $exchange->reference,
            'source_currency' => $exchange->source_currency->value,
            'source_amount' => Money::normalize($exchange->source_amount),
            'destination_currency' => $exchange->destination_currency->value,
            'destination_amount' => Money::normalize($exchange->destination_amount),
            'rate' => Money::normalizeRate($exchange->rate),
            'occurred_at_label' => self::label($exchange->occurred_at),
            'creator' => UserPresenter::identity($exchange->creator),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function cashDeclaration(CashDeclaration $declaration): array
    {
        return [
            'id' => $declaration->id,
            'note' => $declaration->note,
            'counted_usd' => $declaration->counted_usd === null ? null : Money::normalize($declaration->counted_usd),
            'counted_cdf' => $declaration->counted_cdf === null ? null : Money::normalize($declaration->counted_cdf),
            'book_usd' => Money::normalize($declaration->book_usd),
            'book_cdf' => Money::normalize($declaration->book_cdf),
            'gap_usd' => $declaration->gap_usd === null ? null : Money::normalize($declaration->gap_usd),
            'gap_cdf' => $declaration->gap_cdf === null ? null : Money::normalize($declaration->gap_cdf),
            'reference_rate' => $declaration->reference_rate === null ? null : Money::normalizeRate($declaration->reference_rate),
            'rate_effective_at_label' => self::label($declaration->rate_effective_at),
            'indicative_usd' => $declaration->indicative_usd === null ? null : Money::normalize($declaration->indicative_usd),
            'status' => $declaration->status->value,
            'status_label' => $declaration->status->label(),
            'correction_reason' => $declaration->correction_reason,
            'created_at_label' => self::label($declaration->created_at),
            'validated_at_label' => self::label($declaration->validated_at),
            'author' => UserPresenter::identity($declaration->author),
            'validator' => UserPresenter::identity($declaration->validator),
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
