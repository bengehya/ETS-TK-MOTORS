<?php

namespace App\Http\Requests\Supply;

use App\Models\Arrival;
use Illuminate\Foundation\Http\FormRequest;

class RejectArrivalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Arrival $arrival */
        $arrival = $this->route('arrival');

        return $this->user()?->can('reject', $arrival) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:8', 'max:500'],
        ];
    }
}
