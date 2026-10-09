<?php

namespace App\Http\Requests\Stock;

use App\Enums\AdjustmentMotif;
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
            'motif' => ['required', Rule::enum(AdjustmentMotif::class)],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:motif,autre', 'min:8'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif est obligatoire.',
            'reason.required_if' => 'Précisez le motif de cette correction exceptionnelle.',
            'reason.min' => 'La précision du motif doit contenir au moins 8 caractères.',
        ];
    }

    public function location(): Location
    {
        return Location::query()->findOrFail($this->integer('location_id'));
    }
}
