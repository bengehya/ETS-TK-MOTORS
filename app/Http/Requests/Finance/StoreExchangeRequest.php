<?php

namespace App\Http\Requests\Finance;

use App\Enums\Currency;
use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExchangeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('amount'))) {
            $this->merge([
                'amount' => str_replace([' ', ','], ['', '.'], trim($this->input('amount'))),
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
            'source_currency' => ['required', Rule::enum(Currency::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'regex:/^\d+(\.\d{1,2})?$/'],
        ];
    }
}
