<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosticReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_key' => ['required', 'string', 'size:64'],
            'collected_at' => ['required', 'date'],
            'incident_type' => ['required', 'string', 'max:50'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'events' => ['required', 'array', 'max:200'],
            'events.*.time' => ['required', 'date'],
            'events.*.level' => ['required', 'integer', 'between:1,4'],
            'events.*.importance' => ['required', 'in:Alta,Media,Baja'],
            'events.*.category' => ['required', 'string', 'max:80'],
            'events.*.provider' => ['required', 'string', 'max:200'],
            'events.*.id' => ['required', 'integer'],
            'events.*.message' => ['required', 'string', 'max:12000'],
            'system_state' => ['nullable', 'array'],
        ];
    }
}
