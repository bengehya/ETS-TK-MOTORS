<?php

namespace App\Http\Requests\Rentals;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class CloseRentalRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
