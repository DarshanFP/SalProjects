<?php

namespace App\Http\Requests\Projects\CCI;

use Illuminate\Foundation\Http\FormRequest;

class StoreCCIEconomicBackgroundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // EconomicBackground uses fill($request->except('_token')), so we need flexible validation
        // All fields are nullable as the model may have many fields
        return [
            // Add specific field validations based on model structure
            // For now, using a flexible approach - all fields nullable
        ];
    }
}

