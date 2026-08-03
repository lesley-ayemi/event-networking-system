<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_virtual' => ['sometimes', 'boolean'],
            'industry' => ['nullable', 'string', 'max:255'],
            'one_to_one_available' => ['sometimes', 'boolean'],
            'small_group_available' => ['sometimes', 'boolean'],
            'is_free' => ['sometimes', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'accessibility_options' => ['nullable', 'array'],
            'accessibility_options.*' => ['string'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
