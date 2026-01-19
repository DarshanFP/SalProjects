<?php

namespace App\Http\Requests\Projects\IES;

use Illuminate\Foundation\Http\FormRequest;

class StoreIESExpensesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'total_expenses' => 'nullable|numeric|min:0',
            'expected_scholarship_govt' => 'nullable|numeric|min:0',
            'support_other_sources' => 'nullable|numeric|min:0',
            'beneficiary_contribution' => 'nullable|numeric|min:0',
            'balance_requested' => 'nullable|numeric|min:0',
            'particulars' => 'array',
            'particulars.*' => 'nullable|string|max:255',
            'amounts' => 'array',
            'amounts.*' => 'nullable|numeric|min:0',
        ];
    }
}

