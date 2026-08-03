<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'interaction_mode' => ['required', Rule::in(['one_to_one', 'small_group', 'either'])],
            'open_to_matching' => ['required', 'boolean'],
            'message_before_event' => ['required', 'boolean'],
            'preferred_group_size' => ['required', 'integer', 'min:2', 'max:50'],
            'attendance_format' => ['required', Rule::in(['virtual', 'physical'])],
        ];
    }
}
