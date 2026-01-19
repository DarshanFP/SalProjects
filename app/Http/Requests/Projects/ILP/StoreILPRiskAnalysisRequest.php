<?php

namespace App\Http\Requests\Projects\ILP;

use Illuminate\Foundation\Http\FormRequest;

class StoreILPRiskAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'identified_risks' => 'nullable|string|max:1000',
            'mitigation_measures' => 'nullable|string|max:1000',
            'business_sustainability' => 'nullable|string|max:1000',
            'expected_profits' => 'nullable|string|max:1000',
        ];
    }
}

