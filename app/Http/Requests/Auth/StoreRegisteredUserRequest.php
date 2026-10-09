<?php

namespace App\Http\Requests\Auth;

use App\Enums\Civility;
use App\Services\BootstrapRegistrationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreRegisteredUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(BootstrapRegistrationService::class)->isOpen();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'civility' => ['required', Rule::enum(Civility::class)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
