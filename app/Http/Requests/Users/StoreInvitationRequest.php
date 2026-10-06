<?php

namespace App\Http\Requests\Users;

use App\Enums\Civility;
use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageEmployees) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $roles = [Role::Employe->value];

        if ($this->user()?->hasPermission(Permission::InviteBoss)) {
            $roles[] = Role::BossSecondaire->value;
        }

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'civility' => ['required', Rule::enum(Civility::class)],
            'role' => ['required', Rule::in($roles)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.in' => 'Vous n’êtes pas autorisé à attribuer ce rôle.',
            'civility.required' => 'La civilité est obligatoire.',
        ];
    }
}
