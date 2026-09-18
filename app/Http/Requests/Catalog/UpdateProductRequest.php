<?php

namespace App\Http\Requests\Catalog;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $this->user()?->can('update', $product) ?? false;
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
        /** @var Product $product */
        $product = $this->route('product');
        $organizationId = $this->user()?->organization_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')
                    ->where('organization_id', $organizationId)
                    ->ignore($product->id),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')
                    ->where('organization_id', $organizationId)
                    ->ignore($product->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sale_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Product $product */
                $product = $this->route('product');
                $newPrice = $this->input('sale_price');

                if ($newPrice === null) {
                    return;
                }

                if (bccomp((string) $product->sale_price, number_format((float) $newPrice, 2, '.', ''), 2) !== 0) {
                    if (! $this->user()?->can('updatePrice', $product)) {
                        $validator->errors()->add('sale_price', 'Vous n’êtes pas autorisé à modifier le prix.');
                    }
                }
            },
        ];
    }
}
