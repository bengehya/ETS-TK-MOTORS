<?php

namespace App\Http\Requests\Sales;

use App\Enums\Currency;
use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('lines') && $this->filled('product_id')) {
            $this->merge([
                'lines' => [[
                    'product_id' => $this->input('product_id'),
                    'quantity' => $this->input('quantity'),
                ]],
            ]);
        }

        if (! $this->filled('currency')) {
            $this->merge(['currency' => Currency::Usd->value]);
        }

        if (is_string($this->input('amount_received'))) {
            $received = str_replace([' ', ','], ['', '.'], trim($this->input('amount_received')));
            $this->merge(['amount_received' => $received === '' ? null : $received]);
        }

        if ($this->input('client_token') === '') {
            $this->merge(['client_token' => null]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::CreateSales) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->organization_id;

        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('organization_id', $organizationId),
            ],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'amount_received' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'client_token' => ['nullable', 'uuid'],
        ];
    }
}
