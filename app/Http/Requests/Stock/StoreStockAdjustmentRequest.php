<?php

namespace App\Http\Requests\Stock;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
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
            'direction' => ['required', 'in:increase,decrease'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ];
    }

    public function location(): Location
    {
        return Location::query()->findOrFail($this->integer('location_id'));
    }
}
