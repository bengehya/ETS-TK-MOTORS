<?php

namespace App\Http\Requests\Finance;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class RefuseExpenseRequest extends FormRequest
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
            'decision_note' => ['required', 'string', 'max:1000'],
        ];
    }
}
