<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $scale = ['required', 'integer', 'min:1', 'max:5'];

        return [
            'oneToOnePreference' => $scale,
            'preferredGroupSize' => $scale,
            'messageBeforeMeeting' => $scale,
            'structuredConversation' => $scale,
            'responseSpeed' => $scale,
            'virtualPreference' => $scale,
            'networkingGoal' => $scale,
            'industryInterest' => $scale,
            'observeFirstPreference' => $scale,
            'conversationLengthPreference' => $scale,
        ];
    }
}
