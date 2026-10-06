<?php

namespace App\Http\Requests\Finance;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
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
            'amount' => ['required', 'numeric', 'min:0.01', 'regex:/^\d+(\.\d{1,2})?$/'],
            'reason' => ['required', 'string', 'max:1000'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
