<?php

namespace App\Http\Requests\Requests;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class CloseCustomerRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageSales) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $noteRule = $this->routeIs('requests.cancel')
            ? ['required', 'string', 'max:1000']
            : ['nullable', 'string', 'max:1000'];

        return [
            'note' => $noteRule,
        ];
    }
}
