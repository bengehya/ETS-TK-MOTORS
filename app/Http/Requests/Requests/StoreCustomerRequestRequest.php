<?php

namespace App\Http\Requests\Requests;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequestRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('customer_name') === '') {
            $this->merge(['customer_name' => null]);
        }

        if ($this->input('notes') === '') {
            $this->merge(['notes' => null]);
        }

        if ($this->input('designation') === '') {
            $this->merge(['designation' => null]);
        }

        if ($this->input('product_id') === '' || $this->input('product_id') === null) {
            $this->merge(['product_id' => null]);
        }

        if ($this->filled('product_id')) {
            $this->merge(['designation' => null]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::CreateCustomerRequests) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'nullable',
                'integer',
                'required_without:designation',
                'prohibits:designation',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('organization_id', $this->user()->organization_id)),
            ],
            'designation' => ['nullable', 'string', 'max:160', 'required_without:product_id'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'quantity' => ['required', 'integer', 'min:1'],
            'urgent' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
