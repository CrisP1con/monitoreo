<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTelemetrySamplesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'samples' => ['required', 'array', 'min:1', 'max:5000'],
            'samples.*.captured_at' => ['required', 'date'],
            'samples.*.cpu_temperature_c' => ['nullable', 'numeric', 'between:-50,150'],
            'samples.*.gpu_temperature_c' => ['nullable', 'numeric', 'between:-50,150'],
            'samples.*.motherboard_temperature_c' => ['nullable', 'numeric', 'between:-50,150'],
            'samples.*.disk_temperature_c' => ['nullable', 'numeric', 'between:-50,150'],
            'samples.*.memory_used_percent' => ['nullable', 'numeric', 'between:0,100'],
            'samples.*.cpu_package_c' => ['nullable', 'numeric', 'between:-50,150'],
            'samples.*.cpu_core_max_c' => ['nullable', 'numeric', 'between:-50,150'],
            'samples.*.cpu_cores' => ['nullable', 'array'],
            'samples.*.cpu_cores.*' => ['numeric', 'between:-50,150'],
        ];
    }
}
