<?php

namespace App\Http\Requests\Supply;

use App\Models\Arrival;
use Illuminate\Foundation\Http\FormRequest;

class ApproveArrivalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Arrival $arrival */
        $arrival = $this->route('arrival');

        return $this->user()?->can('approve', $arrival) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
