<?php

namespace App\Http\Requests\Requests;

use App\Enums\Permission;
use App\Enums\RestockSuggestionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRestockSuggestionRequest extends FormRequest
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
        return [
            'status' => ['required', Rule::in([
                RestockSuggestionStatus::Reviewed->value,
                RestockSuggestionStatus::Retained->value,
                RestockSuggestionStatus::Rejected->value,
                RestockSuggestionStatus::Decided->value,
            ])],
            'justification' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
