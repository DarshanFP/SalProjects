<?php

namespace App\Http\Requests\Projects\IAH;

use Illuminate\Foundation\Http\FormRequest;

class StoreIAHHealthConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'illness' => 'nullable|string',
            'treatment' => 'nullable|string',
            'doctor' => 'nullable|string|max:255',
            'hospital' => 'nullable|string|max:255',
            'doctor_address' => 'nullable|string',
            'health_situation' => 'nullable|string',
            'family_situation' => 'nullable|string',
        ];
    }
}

