<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Laravel's default ConvertEmptyStringsToNull middleware turns a blank
     * "" input into null before validation runs. These fields are optional
     * text the frontend lets users leave blank, so restore "" here rather
     * than loosening the rules to accept null (which the User model's own
     * '' defaults don't expect).
     */
    protected function prepareForValidation(): void
    {
        foreach (['job_title', 'industry', 'bio', 'networking_goals'] as $field) {
            if ($this->has($field) && $this->input($field) === null) {
                $this->merge([$field => '']);
            }
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'job_title' => ['sometimes', 'string', 'max:255'],
            'industry' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'string', 'max:1000'],
            'networking_goals' => ['sometimes', 'string', 'max:1000'],

            'interaction_preferences' => ['sometimes', 'array'],
            'interaction_preferences.one_to_one' => ['sometimes', 'boolean'],
            'interaction_preferences.small_groups' => ['sometimes', 'boolean'],
            'interaction_preferences.virtual_interaction' => ['sometimes', 'boolean'],
            'interaction_preferences.text_communication' => ['sometimes', 'boolean'],
            'interaction_preferences.meet_before_event' => ['sometimes', 'boolean'],
            'interaction_preferences.observe_first' => ['sometimes', 'boolean'],

            'comfort_settings' => ['sometimes', 'array'],
            'comfort_settings.max_group_size' => ['sometimes', 'integer', 'min:2', 'max:50'],
            'comfort_settings.allow_message_first' => ['sometimes', 'boolean'],
            'comfort_settings.auto_matching' => ['sometimes', 'boolean'],
            'comfort_settings.pre_event_introductions' => ['sometimes', 'boolean'],
            'comfort_settings.event_reminders' => ['sometimes', 'boolean'],

            'onboarding_completed' => ['sometimes', 'boolean'],
        ];
    }
}
