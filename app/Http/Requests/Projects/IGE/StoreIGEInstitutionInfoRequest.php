<?php

namespace App\Http\Requests\Projects\IGE;

use Illuminate\Foundation\Http\FormRequest;

class StoreIGEInstitutionInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'institutional_type' => 'nullable|string|max:255',
            'age_group' => 'nullable|string|max:255',
            'previous_year_beneficiaries' => 'nullable|integer|min:0',
            'outcome_impact' => 'nullable|string',
        ];
    }
}

