<?php

namespace App\Http\Requests\Finance;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashDeclarationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['note', 'counted_usd', 'counted_cdf', 'correction_reason'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($this->routeIs('cash.counts.store')) {
            return $user->hasPermission(Permission::CreateSales) || $user->hasPermission(Permission::ManageExpenses);
        }

        return $user->hasPermission(Permission::ManageExpenses);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:2000', 'required_without_all:counted_usd,counted_cdf'],
            'counted_usd' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'counted_cdf' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'correction_reason' => [$this->routeIs('cash.counts.correct') ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }
}
