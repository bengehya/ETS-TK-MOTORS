<?php

namespace App\Http\Requests;

use App\Enums\Civility;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->exists('civility') && $this->input('civility') === '') {
            $payload['civility'] = null;
        }

        if ($this->exists('first_name')) {
            $payload['first_name'] = trim((string) $this->input('first_name')) ?: null;
        }

        if ($this->exists('last_name')) {
            $payload['last_name'] = trim((string) $this->input('last_name')) ?: null;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'civility' => ['nullable', Rule::enum(Civility::class)],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
