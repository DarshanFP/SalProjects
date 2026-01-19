<?php

namespace App\Http\Requests\Projects\CCI;

use Illuminate\Foundation\Http\FormRequest;

class StoreCCIAgeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // CCI Age Profile has many fields, using a flexible approach
        // All fields are nullable as they may not all be required
        return [
            'education_below_5_bridge_course_prev_year' => 'nullable|integer|min:0',
            'education_below_5_bridge_course_current_year' => 'nullable|integer|min:0',
            'education_below_5_kindergarten_prev_year' => 'nullable|integer|min:0',
            'education_below_5_kindergarten_current_year' => 'nullable|integer|min:0',
            'education_below_5_other_specify' => 'nullable|string|max:255',
            'education_below_5_other_prev_year' => 'nullable|integer|min:0',
            'education_below_5_other_current_year' => 'nullable|integer|min:0',
            // Add more fields as needed - this is a flexible validation
        ];
    }
}

