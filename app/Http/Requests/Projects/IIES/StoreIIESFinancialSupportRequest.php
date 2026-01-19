<?php

namespace App\Http\Requests\Projects\IIES;

use Illuminate\Foundation\Http\FormRequest;

class StoreIIESFinancialSupportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'govt_eligible_scholarship' => 'nullable|string|max:255',
            'scholarship_amt' => 'nullable|numeric|min:0',
            'other_eligible_scholarship' => 'nullable|string|max:255',
            'other_scholarship_amt' => 'nullable|numeric|min:0',
            'family_contrib' => 'nullable|numeric|min:0',
            'no_contrib_reason' => 'nullable|string',
        ];
    }
}

