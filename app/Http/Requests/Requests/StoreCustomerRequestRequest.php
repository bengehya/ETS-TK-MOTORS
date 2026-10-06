<?php

namespace App\Http\Requests\Requests;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequestRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('quantity') === '' || $this->input('quantity') === null) {
            $this->merge(['quantity' => null]);
        }

        if ($this->input('customer_name') === '') {
            $this->merge(['customer_name' => null]);
        }

        if ($this->input('notes') === '') {
            $this->merge(['notes' => null]);
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
                'required',
                'integer',
                Rule::exists('products', 'id')->where('organization_id', $this->user()->organization_id),
            ],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'urgent' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
