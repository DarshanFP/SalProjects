<?php

namespace App\Http\Requests\Projects\RST;

use Illuminate\Foundation\Http\FormRequest;

class StoreRSTTargetGroupAnnexureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'rst_name' => 'array',
            'rst_name.*' => 'nullable|string|max:255',
            'rst_religion' => 'array',
            'rst_religion.*' => 'nullable|string|max:255',
            'rst_caste' => 'array',
            'rst_caste.*' => 'nullable|string|max:255',
            'rst_education_background' => 'array',
            'rst_education_background.*' => 'nullable|string',
            'rst_family_situation' => 'array',
            'rst_family_situation.*' => 'nullable|string',
            'rst_paragraph' => 'array',
            'rst_paragraph.*' => 'nullable|string',
        ];
    }
}

