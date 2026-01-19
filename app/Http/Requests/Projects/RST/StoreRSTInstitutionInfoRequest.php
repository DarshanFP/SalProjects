<?php

namespace App\Http\Requests\Projects\RST;

use Illuminate\Foundation\Http\FormRequest;

class StoreRSTInstitutionInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'year_setup' => 'nullable|string|max:255',
            'total_students_trained' => 'nullable|integer|min:0',
            'beneficiaries_last_year' => 'nullable|integer|min:0',
            'training_outcome' => 'nullable|string',
        ];
    }
}

