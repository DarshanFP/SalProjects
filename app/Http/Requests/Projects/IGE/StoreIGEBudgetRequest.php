<?php

namespace App\Http\Requests\Projects\IGE;

use Illuminate\Foundation\Http\FormRequest;

class StoreIGEBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name.*' => 'nullable|string|max:255',
            'study_proposed.*' => 'nullable|string|max:255',
            'college_fees.*' => 'nullable|numeric|min:0',
            'hostel_fees.*' => 'nullable|numeric|min:0',
            'total_amount.*' => 'nullable|numeric|min:0',
            'scholarship_eligibility.*' => 'nullable|numeric|min:0',
            'family_contribution.*' => 'nullable|numeric|min:0',
            'amount_requested.*' => 'nullable|numeric|min:0',
        ];
    }
}

