<?php

namespace App\Http\Requests\Stock;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('mutateStock', $this->route('product')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where('organization_id', $this->user()?->organization_id),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function location(): Location
    {
        return Location::query()->findOrFail($this->integer('location_id'));
    }
}
