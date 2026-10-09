<?php

namespace App\Http\Requests\Finance;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeRateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('cdf_per_usd'))) {
            $this->merge([
                'cdf_per_usd' => str_replace([' ', ','], ['', '.'], trim($this->input('cdf_per_usd'))),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageExpenses) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cdf_per_usd' => ['required', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,4})?$/'],
        ];
    }
}
