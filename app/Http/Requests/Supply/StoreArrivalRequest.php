<?php

namespace App\Http\Requests\Supply;

use App\Models\Arrival;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArrivalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Arrival::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $reference = trim((string) $this->input('supplier_reference'));

        $this->merge([
            'supplier_reference' => $reference === '' ? null : $reference,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('is_active', true),
            ],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where('organization_id', $organizationId),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'supplier_reference' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function product(): Product
    {
        return Product::query()->findOrFail($this->integer('product_id'));
    }

    public function location(): Location
    {
        return Location::query()->findOrFail($this->integer('location_id'));
    }
}
