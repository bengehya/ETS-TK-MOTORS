<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:max_width=4000,max_height=4000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Choisissez une photo.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'Formats autorisés : JPG, PNG ou WEBP.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'photo.dimensions' => 'L’image est trop grande (4 000 pixels maximum).',
        ];
    }
}
