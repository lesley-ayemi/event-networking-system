<?php

namespace App\Http\Requests\Api;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string', Rule::in(Report::REPORTABLE_TYPES)],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', Rule::in(Report::REASONS)],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
