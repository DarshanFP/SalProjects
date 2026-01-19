<?php

namespace App\Http\Requests\Projects\IES;

use Illuminate\Foundation\Http\FormRequest;

class StoreIESEducationBackgroundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // IESEducationBackground uses fill($request->all()), so we need flexible validation
        // All fields are nullable as the model may have many fields
        return [
            // Add specific field validations based on model structure
            // For now, using a flexible approach
        ];
    }
}

