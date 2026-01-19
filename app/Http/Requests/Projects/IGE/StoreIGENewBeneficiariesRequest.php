<?php

namespace App\Http\Requests\Projects\IGE;

use Illuminate\Foundation\Http\FormRequest;

class StoreIGENewBeneficiariesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'beneficiary_name' => 'array',
            'beneficiary_name.*' => 'nullable|string|max:255',
            'caste' => 'array',
            'caste.*' => 'nullable|string|max:255',
            'address' => 'array',
            'address.*' => 'nullable|string|max:500',
            'group_year_of_study' => 'array',
            'group_year_of_study.*' => 'nullable|string|max:255',
            'family_background_need' => 'array',
            'family_background_need.*' => 'nullable|string|max:500',
        ];
    }
}

