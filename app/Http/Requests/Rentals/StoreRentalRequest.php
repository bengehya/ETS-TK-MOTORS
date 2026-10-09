<?php

namespace App\Http\Requests\Rentals;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageRentals) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0.01', 'regex:/^\d+(\.\d{1,2})?$/'],
            'started_on' => ['required', 'date'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:120'],
        ];
    }
}
