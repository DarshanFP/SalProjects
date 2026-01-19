<?php

namespace App\Http\Requests\Projects\IIES;

use Illuminate\Foundation\Http\FormRequest;

class StoreIIESExpensesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'iies_total_expenses' => 'nullable|numeric|min:0',
            'iies_expected_scholarship_govt' => 'nullable|numeric|min:0',
            'iies_support_other_sources' => 'nullable|numeric|min:0',
            'iies_beneficiary_contribution' => 'nullable|numeric|min:0',
            'iies_balance_requested' => 'nullable|numeric|min:0',
            'iies_particulars' => 'array',
            'iies_particulars.*' => 'nullable|string|max:255',
            'iies_amounts' => 'array',
            'iies_amounts.*' => 'nullable|numeric|min:0',
        ];
    }
}

