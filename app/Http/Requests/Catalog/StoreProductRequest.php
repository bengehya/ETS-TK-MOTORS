<?php

namespace App\Http\Requests\Catalog;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'barcode' => Product::normalizeBarcode($this->input('barcode')),
            'name' => trim((string) $this->input('name')),
            'category' => trim((string) $this->input('category')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')->where('organization_id', $organizationId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->where('organization_id', $organizationId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sale_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }
}
