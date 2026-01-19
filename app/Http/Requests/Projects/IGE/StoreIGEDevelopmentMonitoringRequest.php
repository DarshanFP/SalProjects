<?php

namespace App\Http\Requests\Projects\IGE;

use Illuminate\Foundation\Http\FormRequest;

class StoreIGEDevelopmentMonitoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'proposed_activities' => 'nullable|string',
            'monitoring_methods' => 'nullable|string',
            'evaluation_process' => 'nullable|string',
            'conclusion' => 'nullable|string',
        ];
    }
}

