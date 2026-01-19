<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreSustainabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'sustainability' => 'nullable|string',
            'monitoring_process' => 'nullable|string',
            'reporting_methodology' => 'nullable|string',
            'evaluation_methodology' => 'nullable|string',
        ];
    }
}

